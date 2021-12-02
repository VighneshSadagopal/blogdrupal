<?php

namespace Drupal\website_feedback\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;

/**
 * Defines the 'website_feedback_screenshot' field widget.
 *
 * @FieldWidget(
 *   id = "website_feedback_screenshot",
 *   label = @Translation("Screenshot image"),
 *   field_types = {"image"},
 * )
 */
class ScreenshotWidget extends FileWidget {

  /**
   * ID for html2canvas screenshot technology.
   */
  const HTML2CANVAS = 'html2canvas';

  /**
   * ID for getDisplayMedia screenshot technology.
   */
  const GET_DISPLAY_MEDIA = 'getDisplayMedia';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'technology' => self::HTML2CANVAS,
    ] + parent::defaultSettings();
  }

  /**
   * Gets the available screenshot technologies.
   *
   * @return array|string
   *   A list of technologies
   */
  public static function getTechnologies() {
    return [
      self::HTML2CANVAS,
      self::GET_DISPLAY_MEDIA,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $technologies = self::getTechnologies();
    $element['technology'] = [
      '#type' => 'select',
      '#title' => $this->t('Screenshot technology'),
      '#options' => array_combine($technologies, $technologies),
      '#default_value' => $this->getSetting('technology'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Screenshot technology: @technology', ['@technology' => $this->getSetting('technology')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $class = static::class;
    $element['#process'][] = [$class, 'processScreenshotWidget'];
    $element['#original_value_callback'] = $element['#value_callback'];
    $element['#value_callback'] = [$class, 'valueScreenshotWidget'];
    $element['#description_display'] = 'none';
    $element['take_screenshot'] = [
      '#type' => 'html_tag',
      '#tag' => 'input',
      '#attributes' => [
        'type' => 'button',
        'value' => $this->t('Take a screenshot'),
        'class' => [
          'button',
          'take-screenshot-button',
        ],
      ],
    ];
    $use_cdn = \Drupal::config('website_feedback.settings')->get('html2canvas_cdn');
    $element['screenshot_data'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['screenshot-data'],
      ],
      '#attached' => [
        'library' => [
          'website_feedback/screenshot',
          'website_feedback/html2canvas' . ($use_cdn ? '-cdn' : ''),
        ],
        'drupalSettings' => [
          'websiteFeedback' => [
            'screenshotTechnology' => $this->getSetting('technology'),
          ],
        ],
      ],
    ];

    return $element;
  }

  /**
   * Form API callback: Processes a screenshot image widget element.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function processScreenshotWidget($element, FormStateInterface $form_state, $form) {
    $element['upload']['#attributes']['class'][] = 'hidden';
    unset($element['upload_button']['#attributes']['class']);
    $element['upload_button']['#attributes']['class'][] = 'upload-button';
    $element['upload_button']['#attributes']['class'][] = 'hidden';
    // If we already have an image, we don't want to show the screenshot button.
    if (!empty($element['#value']['fids'])) {
      $element['take_screenshot']['#access'] = FALSE;
    }
    return $element;
  }

  /**
   * Render API callback: Hides screenshot button.
   *
   * Take screenshot button is hidden when a file is already uploaded. Remove
   * controls.
   */
  public static function preRenderScreenshotWidget($element) {
    // If we already have an image, we don't want to show the screenshot button.
    if (!empty($element['#value']['fids'])) {
      $element['take_screenshot']['#access'] = FALSE;
    }
    return $element;
  }

  /**
   * Value callback for screenshot image widget element.
   */
  public static function valueScreenshotWidget(&$element, $input, FormStateInterface $form_state) {
    if (($input !== FALSE) && !empty($input['screenshot_data'])) {
      if ($data = base64_decode(str_replace('data:image/jpeg;base64,', '', $input['screenshot_data']))) {

        // See file_save_data();
        // Code below from file_save_data function.
        $destination = isset($element['#upload_location']) ? $element['#upload_location'] : NULL;
        if (isset($destination) && !\Drupal::service('file_system')->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
          \Drupal::logger('file')->notice('The upload directory %directory for the file field %name could not be created or is not accessible. A newly uploaded file could not be saved in this directory as a consequence, and the upload was canceled.', [
            '%directory' => $destination,
            '%name' => $element['#field_name'],
          ]);
          $form_state->setError($element, t('The file could not be uploaded.'));
          return FALSE;
        }

        $destination = "{$destination}/" . Crypt::randomBytesBase64() . '.jpg';
        $replace = FileSystemInterface::EXISTS_RENAME;
        $user = \Drupal::currentUser();

        if (empty($destination)) {
          $destination = \Drupal::config('system.file')->get('default_scheme') . '://';
        }
        /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
        $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
        if (!$stream_wrapper_manager->isValidUri($destination)) {
          \Drupal::logger('file')->notice('The data could not be saved because the destination %destination is invalid. This may be caused by improper use of file_save_data() or a missing stream wrapper.', ['%destination' => $destination]);
          \Drupal::messenger()->addError(t('The data could not be saved because the destination is invalid. More information is available in the system log.'));
          return FALSE;
        }

        if ($uri = \Drupal::service('file_system')->saveData($data, $destination, $replace)) {
          // Create a file entity.
          $file = File::create([
            'uri' => $uri,
            'uid' => $user->id(),
            // We need files with permanent status.
            // Anonymous users do not have access to temporary ones.
            'status' => 1,
          ]);
          if ($replace == FileSystemInterface::EXISTS_RENAME && is_file($destination)) {
            /** @var \Drupal\Core\File\FileSystemInterface $file_system */
            $file_system = \Drupal::service('file_system');
            $file->setFilename($file_system->basename($destination));
          }

          $file->save();
        }
      }

      $input['fids'] = ($file) ? $file->id() : '';
      $input['screenshot_data'] = '';
    }

    return call_user_func_array($element['#original_value_callback'], [
      &$element,
      $input,
      &$form_state,
    ]);
  }

}
