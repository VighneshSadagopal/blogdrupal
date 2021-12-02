<?php

namespace Drupal\website_feedback\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;
use Drupal\website_feedback\WebsiteFeedbackInterface;

/**
 * Resolve Website Feedback.
 *
 * @Action(
 *   id = "website_feedback_resolve_action",
 *   label = @Translation("Mark Website Feedback as resolved"),
 *   type = "website_feedback"
 * )
 */
class ResolveWebsiteFeedback extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['status' => WebsiteFeedbackInterface::RESOLVED];
  }

}
