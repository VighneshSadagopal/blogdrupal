<?php

namespace Drupal\website_feedback\Form;

use Drupal\Core\Entity\Form\DeleteMultipleForm as EntityDeleteMultipleForm;
use Drupal\Core\Url;

/**
 * Provides the website_feedback multiple delete confirmation form.
 *
 * @internal
 */
class ConfirmDeleteMultiple extends EntityDeleteMultipleForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->selection), 'Are you sure you want to delete this Website Feedback?', 'Are you sure you want to delete these Website Feedback items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.website_feedback.collection');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletedMessage($count) {
    return $this->formatPlural($count, 'Deleted @count Website Feedback.', 'Deleted @count Website Feedback items.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getInaccessibleMessage($count) {
    return $this->formatPlural($count, "@count Website Feedback has not been deleted because you do not have the necessary permissions.", "@count Website Feedback items have not been deleted because you do not have the necessary permissions.");
  }

}
