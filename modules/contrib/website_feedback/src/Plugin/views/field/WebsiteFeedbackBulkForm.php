<?php

namespace Drupal\website_feedback\Plugin\views\field;

use Drupal\views\Plugin\views\field\BulkForm;

/**
 * Defines a website_feedback operations bulk form element.
 *
 * @ViewsField("website_feedback_bulk_form")
 */
class WebsiteFeedbackBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No feedback items selected.');
  }

}
