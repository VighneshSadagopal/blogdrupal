<?php

namespace Drupal\website_feedback\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\website_feedback\Plugin\Field\FieldWidget\ScreenshotWidget;

/**
 * Configure Website Feedback settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'website_feedback_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['website_feedback.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('website_feedback.settings');

    $form['type_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable feedback type selector'),
      '#description' => $this->t('This allows users to choose feedback type: Feedback, Support, Bug Report'),
      '#default_value' => $config->get('type_enabled'),
    ];

    $form['tags_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable feedback tags using taxonomy'),
      '#default_value' => $config->get('tags_enabled'),
    ];

    $vocabularies = taxonomy_vocabulary_get_names();
    $form['tags_vocabulary'] = [
      '#type' => 'select',
      '#options' => $vocabularies,
      '#title' => $this->t('Select a vocabulary to use for the feedback tags.'),
      '#default_value' => $config->get('tags_vocabulary'),
      '#states' => [
        'visible' => [
          ':input[name="tags_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $technologies = ScreenshotWidget::getTechnologies();
    $form['screenshot_technology'] = [
      '#type' => 'select',
      '#title' => $this->t('Screenshot technology'),
      '#options' => array_combine($technologies, $technologies),
      '#default_value' => $config->get('screenshot_technology'),
    ];

    $form['button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Feedback button text'),
      '#maxlength' => 64,
      '#default_value' => $config->get('button_text'),
    ];

    $form['button_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Feedback button title attribute'),
      '#description' => $this->t('Displays on mouse over.'),
      '#maxlength' => 255,
      '#default_value' => $config->get('button_title'),
    ];

    $form['success_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Success message'),
      '#description' => $this->t('Shows to the user after the feedback was saved.'),
      '#maxlength' => 255,
      '#default_value' => $config->get('success_message'),
    ];

    $form['html2canvas_cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load Html2canvas library from CDN'),
      '#description' => $this->t('Checking this box will cause the Html2canvas library, using to make screenshots, to be loaded from the jsdelivr.net CDN rather than from the local library file.'),
      '#default_value' => $config->get('html2canvas_cdn'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $path = '/libraries/html2canvas/html2canvas.min.js';
    if (!$form_state->getValue('html2canvas_cdn') && !is_file(\Drupal::root() . $path)) {
      $this->messenger()->addWarning($this->t('The module is configured to not use CDN for the @name library, but it\'s not found locally. You either should install <a href=":url">@name library</a> locally at @path or enable the \'Load Html2canvas library from CDN\' option.', [
        '@name' => 'Html2canvas',
        ':url' => 'https://github.com/niklasvh/html2canvas',
        '@path' => $path,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('website_feedback.settings');
    $tags_vocabulary = $form_state->getValue('tags_vocabulary');
    $tags_vocabulary_changed = ($config->get('tags_vocabulary') !== $tags_vocabulary);
    $screenshot_technology_changed = ($config->get('screenshot_technology') !== $form_state->getValue('screenshot_technology'));
    $config->set('type_enabled', $form_state->getValue('type_enabled'))
      ->set('tags_enabled', $form_state->getValue('tags_enabled'))
      ->set('tags_vocabulary', $tags_vocabulary)
      ->set('button_text', $form_state->getValue('button_text'))
      ->set('button_title', $form_state->getValue('button_title'))
      ->set('screenshot_technology', $form_state->getValue('screenshot_technology'))
      ->set('success_message', $form_state->getValue('success_message'))
      ->set('html2canvas_cdn', $form_state->getValue('html2canvas_cdn'))
      ->save();

    // Force update website_feedback entity field definitions.
    if ($tags_vocabulary_changed || $screenshot_technology_changed) {
      \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    }

    parent::submitForm($form, $form_state);
  }

}
