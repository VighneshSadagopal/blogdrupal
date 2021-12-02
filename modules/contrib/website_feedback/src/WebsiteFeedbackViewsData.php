<?php

namespace Drupal\website_feedback;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the website_feedback entity type.
 */
class WebsiteFeedbackViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['website_feedback']['website_feedback_bulk_form'] = [
      'title' => $this->t('Website Feedback operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple Website Feedback items.'),
      'field' => [
        'id' => 'website_feedback_bulk_form',
      ],
    ];
    return $data;
  }

}
