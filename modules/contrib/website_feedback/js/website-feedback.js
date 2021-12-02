(function ($) {
  Drupal.behaviors.websiteFeedback = {
    attach: function (context, settings) {
      // Checking if script is running inside a iframe if yes then do not add
      // button see http://drupal.org/node/1683086
      if (top == self && context === document) {
        $('body').once('website-feedback').each(function () {
          var $link = $('<a href="/admin/content/website-feedback/add" id="website-feedback-button" class="button button-website-feedback"></a>')
            .html(drupalSettings.websiteFeedback.buttonText)
            .attr('title', drupalSettings.websiteFeedback.buttonTitle);
          $('<div class="website-feedback-toggle-wrapper"></div>')
            .append($link)
            .appendTo('body')

          const elementSettings = {
            progress: { type: 'throbber' },
            dialogType: 'modal',
            base: $link.attr('id'),
            element: $link[0],
            url: $link.attr('href'),
            event: 'click',
            dialog: {
              width: ($(document).width() < 600 ? '90%' : 500),
              position: { my: "right bottom", at: "right-10 bottom-10" },
              show: 'fadeIn',
              title: 'Send feedback',
              classes: {
                "ui-dialog": "website-feedback-dialog"
              }
            }
          };

          Drupal.ajax(elementSettings);
        });

      }
    }
  };
})(jQuery);
