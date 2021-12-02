<?php

namespace Drupal\website_feedback\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the website feedback entity edit forms.
 */
class WebsiteFeedbackForm extends ContentEntityForm {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a WebsiteFeedbackForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, RendererInterface $renderer) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    if ($this->operation === 'add') {
      $actions['submit']['#value'] = $this->t('Send');
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    if ($this->operation === 'edit') {
      $form['url']['#disabled'] = TRUE;
    }
    else {
      $form['url']['#access'] = FALSE;
      $form['status']['#access'] = FALSE;
    }
    $config = $this->config('website_feedback.settings');
    if (!$config->get('type_enabled')) {
      $form['type']['#access'] = FALSE;
    }
    if (!$config->get('tags_enabled')) {
      $form['tags']['#access'] = FALSE;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    $element = parent::processForm($element, $form_state, $form);
    $element["image"]["widget"][0]["#process"][] = '::processImage';
    // Add AJAX submit if this is an add form requested by AJAX.
    if ($this->operation === 'add' && $this->getRequest()->isXmlHttpRequest()) {
      $element['actions']['submit']['#ajax'] = [
        'callback' => '::ajaxFormSubmit',
        'wrapper' => $element['#id'],
        'effect' => 'fade',
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    if ($this->operation === 'add') {
      $entity->url = $this->getRequest()->headers->get('referer');
    }
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result == SAVED_NEW) {
      $this->logger('website_feedback')->notice('Created new website feedback %label', $logger_arguments);
      if ($this->getRequest()->isXmlHttpRequest()) {
        return;
      }
      $success_message = $this->config('website_feedback.settings')->get('success_message');
      $this->messenger()->addStatus($success_message);
      $form_state->setRedirectUrl(Url::fromUri($this->getRequest()->headers->get('referer')));
    }
    else {
      $this->messenger()->addStatus($this->t('The website feedback %label has been updated.', $message_arguments));
      $this->logger('website_feedback')->notice('Updated new website feedback %label.', $logger_arguments);
      $form_state->setRedirectUrl($entity->toUrl('canonical'));
    }

  }

  /**
   * Form API callback: Processes image in website_feedback form.
   *
   * Removes Upload/Remove buttons and ajax upload for security reason.
   */
  public function processImage($element, FormStateInterface $form_state, $form) {
    if ($this->operation === 'add') {
      $element['upload_button']['#access'] = FALSE;
      $element['remove_button']['#access'] = FALSE;
    }
    return $element;
  }

  /**
   * Ajax callback for Website Feedback add form.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   Render array or Response object
   */
  public function ajaxFormSubmit(array $form, FormStateInterface $form_state) {
    // If errors, return the form with errors and messages.
    if ($form_state->hasAnyErrors()) {
      return $form;
    }
    $this->submitForm($form, $form_state);
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->config('website_feedback.settings')->get('success_message'),
      '#attributes' => ['class' => ['website-feedback-success-message']],
    ];
  }

}
