Drupal.websiteFeedback = {};

(function ($, win, doc, websiteFeedback) {

  "use strict";

  var Region = function (type, startX, startY) {
    this.startX = startX;
    this.startY = startY;
    this.type = type;
    this.left = undefined;
    this.top = undefined;
    this.width = undefined;
    this.height = undefined;
    var css_name_base = 'website-feedback-region';

    this.show = function () {
      if(!websiteFeedback.isDrawing) {
        $(this.region).addClass('visible');
        $(this.closeButton).addClass('visible');
      }
    };

    this.hide = function () {
      $(this.region).removeClass('visible');
      $(this.closeButton).removeClass('visible');
    };

    this.remove = function () {
      $(this.region).remove();
      websiteFeedback.removeRegion(this);
    };

    this.region = $('<div></div>')
      .addClass(css_name_base + ' ' + css_name_base + '_' + type)
      .on('mouseenter', $.proxy(this.show, this))
      .on('mouseleave', $.proxy(this.hide, this))
      .css({
        left: this.startX + 'px',
        top: this.startY + 'px',
      })
      .appendTo('body')
      .get(0);

    this.closeButton = $('<div></div>')
      .addClass(css_name_base + '-close ' + css_name_base + '-close_' + type)
      .on('mouseenter', $.proxy(this.show, this))
      .on('mouseleave', $.proxy(this.hide, this))
      .on('mousedown', $.proxy(this.remove, this))
      .appendTo(this.region)
      .get(0);

  };

  var Rectangle = function (type, startX, startY) {
    Region.call(this, type, startX, startY);

    this.draw = function (e) {
      const clientX = typeof e.clientX !== 'undefined' ? e.clientX : e.touches[0].clientX,
        clientY = typeof e.clientY !== 'undefined' ? e.clientY : e.touches[0].clientY;
      websiteFeedback.isDrawing = true;
      this.left = Math.min(this.startX, clientX);
      this.top = Math.min(this.startY, clientY);
      this.width = Math.abs(clientX - this.startX);
      this.height = Math.abs(clientY - this.startY);
      $(this.region).css({
        left: this.left + 'px',
        top: this.top + 'px',
        width: this.width + 'px',
        height: this.height + 'px',
      });
      websiteFeedback.scheduleHighlight();
    };

    this.finishDraw = function (e) {
      $('body').off('mousemove touchmove', this.draw)
        .off('mouseup touchend', this.finishDraw);
      websiteFeedback.isDrawing = false;
      // remove very small regions
      if(this.width < 5 && this.height < 5) {
        this.remove();
      }
    };

    $('body').on('mousemove touchmove', $.proxy(this.draw, this))
      .on('mouseup touchend', $.proxy(this.finishDraw, this));
  };
  Rectangle.prototype = Object.create(Region.prototype);

  var Note = function (type, startX, startY) {
    Region.call(this, type, startX, startY);

    var textarea = $('<textarea class="website-feedback-note-textarea" rows="6" cols="18"></textarea>')
      .on('keydown', function (e) {
        return true;
        if (e.keyCode !== 8 && $(this).val().length >= 108) {
          return false;
        }
      })
      .appendTo(this.region);
    setTimeout(function () {textarea.focus()}, 0);
    $('#website-feedback-highlight').click();
  };
  Note.prototype = Object.create(Region.prototype);

  websiteFeedback = {
    activeTool: "highlight",
    canvas: undefined,
    highlightRegions: [],
    screenshotBase64: 0,
    currentWindowWidth: $(window).width(),
    currentWindowHeight: $(window).height(),
    screenshotTargetFormItem: undefined,
    screenshotControls: undefined,
    isDrawing: false
  };

  websiteFeedback.rect = function (left, top, width, height) {
    this.active = 1;
    this.left   = left;
    this.top    = top;
    this.width  = width;
    this.height = height;
  };

  websiteFeedback.cropScreenshotCanvas = function (canvas) {
    var cropped_canvas = document.createElement('canvas');
    var width = this.canvas.width,
      height = this.canvas.height,
      left = 0,
      top = window.scrollY;
    cropped_canvas.width = this.canvas.width;
    cropped_canvas.height = this.canvas.height;
    cropped_canvas.getContext("2d").drawImage(
      canvas,
      left, top, width, height, // source rect with content to crop
      0, 0, width, height); // newCanvas, same size as source rect
    return cropped_canvas;
  };

  websiteFeedback.processScreenshot = function (screenshotCanvas) {
    var thumb_width, thumb_height, max_size = 100;
    var screenshot = screenshotCanvas.toDataURL('image/jpeg', 0.85);
    this.closeScreenshot();
    $('.screenshot-data', this.screenshotTargetFormItem).val(screenshot).change();
    // place a thumbnail
    $('.website-feedback-screenshot-thumbnail', this.screenshotTargetFormItem).remove();
    if(screenshotCanvas.width > screenshotCanvas.height) {
      thumb_width = max_size;
      thumb_height = screenshotCanvas.height * max_size / screenshotCanvas.width;
    }
    else {
      thumb_height = max_size;
      thumb_width = screenshotCanvas.width * max_size / screenshotCanvas.height;
    }
    var thumb = $('<img class="website-feedback-screenshot-thumbnail">')
      .attr('src', screenshot)
      .css({
        width: thumb_width + 'px',
        height: thumb_height + 'px'
      })
    $('label', this.screenshotTargetFormItem).after(thumb);
    // Fire window resize to update dialog position
    setTimeout(function () {
      $(window).resize();
    });
  };

  websiteFeedback.getScreenshotFromMediaDevices = async function () {
    const stream = await navigator.mediaDevices.getDisplayMedia({
      video: { mediaSource: 'screen' },
    });
    await new Promise(function (resolve) {
      // wait while choose window dialog hides
      setTimeout(resolve, 500);
    });
    const track = stream.getVideoTracks()[0];
    // init Image Capture and not Video stream
    const imageCapture = new ImageCapture(track);
    // take first frame only
    const bitmap = await imageCapture.grabFrame();
    track.stop();
    const canvas = document.createElement('canvas');
    canvas.width = bitmap.width;
    canvas.height = bitmap.height;
    const context = canvas.getContext('2d');
    context.drawImage(bitmap, 0, 0, bitmap.width, bitmap.height);
    return canvas;
  };

  websiteFeedback.takeScreenshot = function () {
    if (!this.screenshotTargetFormItem || !$(this.screenshotTargetFormItem).length) {
      return;
    }
    var screenshot_element = document.body;
    $(this.screenshotControls).hide();
    var self = this;
    if (drupalSettings.websiteFeedback.screenshotTechnology === 'getDisplayMedia' && navigator.mediaDevices && navigator.mediaDevices.getDisplayMedia) {
      websiteFeedback.getScreenshotFromMediaDevices().then(function (canvas) {
        self.processScreenshot(canvas);
      });
    }
    else {
      html2canvas(screenshot_element, {useCORS: true, async: false}).then(function (canvas) {
        var screenshotCanvas = self.cropScreenshotCanvas(canvas);
        self.processScreenshot(screenshotCanvas);
      });
    }

  };

  websiteFeedback.onResize = function () {
    this.currentWindowWidth = $(window).width();
    this.currentWindowHeight = $(window).height();
    this.highlight();
  };

  websiteFeedback.blockSelectstart = function (e) {
    if (!$(e.target).is('.website-feedback-region_note, .website-feedback-note-textarea')) {
      return false;
    }
  };

  // prepare and show screenshot controls
  websiteFeedback.requestScreenshot = function ($target_form_item) {
    this.screenshotTargetFormItem = $target_form_item;
    if ($('.website-feedback-dialog').length) {
      $('.website-feedback-dialog, .ui-widget-overlay').fadeOut('fast');
    }
    $('body').addClass('website-feedback-screenshot-requested')
      .on('selectstart', websiteFeedback.blockSelectstart)
      .on('dblclick', false)
      .on('mousedown touchstart', $.proxy(this.startDraw, this));
    this.canvas = $('<canvas id="website-feedback-canvas" class="website-feedback-canvas">' + Drupal.t('Your browser does not support canvas element') + '</canvas>')
      .attr('width', $(window).width())
      .attr('height', $(window).height())
      .prependTo($('body'))
      .get(0);
    $(window).on('resize', $.proxy(this.onResize, this));

    this.onResize();
    this.scheduleHighlight();
    $(this.getScreenshotControls()).show();
  }

  websiteFeedback.closeScreenshot = function () {
    $('body').removeAttr('unselectable')
      .css('user-select', false)
      .removeClass('website-feedback-screenshot-requested')
      .off('selectstart', this.blockSelectstart)
      .off('dblclick', false)
      .off('mousedown touchstart', this.startDraw);
    $(this.canvas).remove();
    $(this.screenshotControls).remove();
    this.screenshotControls = null;
    $('.website-feedback-region').remove();
    this.highlightRegions = [];
    $(window).off('resize', this.onResize);
    if ($('.website-feedback-dialog').length) {
      $('.website-feedback-dialog, .ui-widget-overlay').fadeIn();
    }
  };

  websiteFeedback.switchTool = function (event) {
    const $el = $(event.target);
    const tool = $el.data('tool');
    if (tool && websiteFeedback.activeTool !== tool) {
      $('.website-feedback-controls__drawer-button').removeClass('active');
      $el.addClass('active')
        .blur();
      websiteFeedback.activeTool = tool;
      $(websiteFeedback.canvas).attr('data-screenshot-tool', tool);
    }
  };

  websiteFeedback.getScreenshotControls = function () {
    if (!this.screenshotControls) {
      const button_classes = 'button button--small button_screenshot-control';
      const drawer_button_classes = button_classes + ' website-feedback-controls__drawer-button';
      const highlight_button = $('<input type="button" data-tool="highlight" value="' + Drupal.t('Highlight') + '" id="website-feedback-highlight" class="' + drawer_button_classes + ' button_highlight active">')
        .on('click', this.switchTool);
      const blackout_button = $('<input type="button" data-tool="blackout" value="' + Drupal.t('Blackout') + '" id="website-feedback-blackout" class="' + drawer_button_classes + ' button_blackout">')
        .on('click', this.switchTool);
      const note_button = $('<input type="button" data-tool="note" value="' + Drupal.t('Add note') + '" id="website-feedback-add-note" class="' + drawer_button_classes + ' button_add-note">')
        .on('click', this.switchTool);
      const drawer = $('<div class="website-feedback-controls__drawer"></div>')
        .append(highlight_button)
        .append(blackout_button)
        .append(note_button);
      const screenshot_button = $('<input type="button" id="website-feedback-do-take-screenshot" class="' + button_classes + ' button--primary">')
        .attr('value', Drupal.t('Take a screenshot'))
        .on('click', $.proxy(this.takeScreenshot, this));
      const cancel_button = $('<input type="button" id="website-feedback-cancel-screenshot" class="' + button_classes + '">')
        .attr('value', Drupal.t('Cancel'))
        .on('click', $.proxy(this.closeScreenshot, this));
      const actions = $('<div class="website-feedback-controls__actions"></div>')
        .append(screenshot_button)
        .append(cancel_button);
      this.screenshotControls = $('<div id="website-feedback-controls" class="website-feedback-controls"></div>')
        .hide()
        .append(drawer)
        .append(actions)
        .appendTo('body')
        .get(0);
      this.activeTool = 'highlight';
    }
    return this.screenshotControls;
  }

  websiteFeedback.startDraw = function (e) {
    const skip_on_elements = '.website-feedback-region-close, .website-feedback-region_note, #website-feedback-controls';
    if ($(e.target).is(skip_on_elements) || $(e.target).parents(skip_on_elements).length) {
      return true;
    }
    const clientX = typeof e.clientX !== 'undefined' ? e.clientX : e.touches[0].clientX,
      clientY = typeof e.clientY !== 'undefined' ? e.clientY : e.touches[0].clientY;
    var region;
    if (this.activeTool === 'note') {
      region = new Note(this.activeTool, clientX, clientY);
    }
    else {
      region = new Rectangle(this.activeTool, clientX, clientY);
    }
    if(region.type === 'highlight') {
      this.highlightRegions.push(region);
    }
  }

  websiteFeedback.scheduleHighlight = function () {
    var self = this;
    setTimeout(function () {
      self.highlight();
    }, 0);
  };

  websiteFeedback.highlight = function () {
    var feedbackCanvas = document.getElementById("website-feedback-canvas"),
      context = feedbackCanvas.getContext('2d');
    //Drawing a dimmer on whole page
    context.globalAlpha = 0.3;
    context.fillStyle   = 'black';
    context.clearRect(0,0,this.currentWindowWidth,this.currentWindowHeight);
    context.fillRect(0,0,this.currentWindowWidth,this.currentWindowHeight);
    context.globalAlpha = 1;
    context.strokeStyle = 'black';
    context.lineWidth   = 1;

    this.highlightRegions.forEach(function (region, index, regions_array) {
      context.strokeRect(region.left - 0.5, region.top - 0.5, region.width + 1, region.height + 1);
    });

    this.highlightRegions.forEach(function (region, index, regions_array) {
      context.clearRect(region.left, region.top, region.width, region.height);
    })

  };

  websiteFeedback.removeRegion = function (item) {
    var index = this.highlightRegions.indexOf(item);
    if (index > -1) {
      this.highlightRegions.splice(index, 1);
      this.highlight();
    }
  };

  Drupal.behaviors.websiteFeedbackScreenshot = {
    attach: function (context, settings) {
      $('.take-screenshot-button').once('screenshot-image-init').each(function () {
        this.addEventListener('click', function (event) {
          websiteFeedback.requestScreenshot($(event.target).closest('.js-form-item')[0]);
        });
      });
    }
  }

}(jQuery, window, document, Drupal.websiteFeedback));
