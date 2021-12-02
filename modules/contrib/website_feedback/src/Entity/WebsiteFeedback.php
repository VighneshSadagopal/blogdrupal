<?php

namespace Drupal\website_feedback\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;
use Drupal\website_feedback\WebsiteFeedbackInterface;

/**
 * Defines the website feedback entity class.
 *
 * @ContentEntityType(
 *   id = "website_feedback",
 *   label = @Translation("Website Feedback"),
 *   label_collection = @Translation("Website Feedbacks"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\website_feedback\WebsiteFeedbackListBuilder",
 *     "views_data" = "Drupal\website_feedback\WebsiteFeedbackViewsData",
 *     "access" = "Drupal\website_feedback\WebsiteFeedbackAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\website_feedback\Form\WebsiteFeedbackForm",
 *       "edit" = "Drupal\website_feedback\Form\WebsiteFeedbackForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "website_feedback",
 *   admin_permission = "administer website feedback",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "summary",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   links = {
 *     "add-form" = "/admin/content/website-feedback/add",
 *     "canonical" = "/website_feedback/{website_feedback}",
 *     "edit-form" = "/admin/content/website-feedback/{website_feedback}/edit",
 *     "delete-form" = "/admin/content/website-feedback/{website_feedback}/delete",
 *     "delete-multiple-form" = "/admin/content/website-feedback/delete",
 *     "collection" = "/admin/content/website-feedback"
 *   },
 * )
 */
class WebsiteFeedback extends ContentEntityBase implements WebsiteFeedbackInterface {

  /**
   * {@inheritdoc}
   *
   * When a new website feedback entity is created, set the uid entity reference
   * to the current user as the creator of the entity.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['uid' => \Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('summary')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('summary', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $config = \Drupal::config('website_feedback.settings');

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['summary'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Summary'))
      ->setDescription(t('The summary of the feedback.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('URL'))
      ->setDescription(t('URL at which the form was submitted.'))
      ->setDisplayOptions('form', [
        'type' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'uri_link',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Feedback description.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['type'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Type'))
      ->setDescription(t('Select feedback type.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDefaultValue(self::TYPE_FEEDBACK)
      ->setSetting('unsigned', TRUE)
      ->setSetting('allowed_values', [
        self::TYPE_FEEDBACK => 'Feedback',
        self::TYPE_SUPPORT => 'Support request',
        self::TYPE_BUG => 'Bug report',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 5,
      ]);

    $fields['tags'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tags'))
      ->setDescription(t('Taxonomy terms, related to the feedback.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'settings' => [
          'link' => FALSE,
        ],
        'weight' => 30,
      ]);
    if ($vid = $config->get('tags_vocabulary')) {
      $fields['tags']->setSetting('handler_settings', ['target_bundles' => [$vid => $vid]]);
    }

    $fields['screenshot'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Screenshot'))
      ->setDescription(t('Store page screenshot.'))
      ->setSettings([
        'alt_field' => 0,
        'alt_field_required' => 0,
        'file_directory' => 'website_feedback/screenshots/[date:custom:Y]-[date:custom:m]',
        'max_filesize' => '10MB',
      ])
      ->setDisplayOptions('view', [
        'type' => 'image',
        'label' => 'above',
        'weight' => 20,
        'settings' => [
          'image_style' => 'thumbnail',
          'image_link' => 'file',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'website_feedback_screenshot',
        'settings' => [
          'technology' => $config->get('screenshot_technology'),
        ],
        'weight' => 15,
      ]);

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Image'))
      ->setDescription(t('Upload your image.'))
      ->setSettings([
        'alt_field' => 0,
        'alt_field_required' => 0,
        'file_directory' => 'website_feedback/images/[date:custom:Y]-[date:custom:m]',
        'max_filesize' => '10MB',
      ])
      ->setDisplayOptions('view', [
        'type' => 'image',
        'label' => 'above',
        'weight' => 25,
        'settings' => [
          'image_style' => 'thumbnail',
          'image_link' => 'file',
        ],
      ])
      ->setDisplayOptions('form', ['weight' => 20]);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user ID of the website feedback author.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 25,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'author',
        'weight' => 40,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the website feedback was created.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 35,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('A boolean indicating whether the issue or question is resolved.'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', 'Resolved')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 35,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'inline',
        'weight' => 10,
        'settings' => [
          'format' => 'custom',
          'format_custom_true' => 'Resolved',
          'format_custom_false' => 'Active',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
