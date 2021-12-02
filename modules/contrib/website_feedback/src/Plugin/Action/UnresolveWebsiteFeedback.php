<?php

namespace Drupal\website_feedback\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;
use Drupal\website_feedback\WebsiteFeedbackInterface;

/**
 * Unresolve Website Feedback.
 *
 * @Action(
 *   id = "website_feedback_unresolve_action",
 *   label = @Translation("Remove Resolved mark from Website Feedback"),
 *   type = "website_feedback"
 * )
 */
class UnresolveWebsiteFeedback extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['status' => WebsiteFeedbackInterface::NOT_RESOLVED];
  }

}
