<?php

namespace Drupal\website_feedback;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a website feedback entity type.
 */
interface WebsiteFeedbackInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Denotes that the website feedback is not resolved.
   */
  const NOT_RESOLVED = 0;

  /**
   * Denotes that the website feedback is resolved.
   */
  const RESOLVED = 1;

  /**
   * Key for common feedback type.
   */
  const TYPE_FEEDBACK = 0;

  /**
   * Key for support request feedback type.
   */
  const TYPE_SUPPORT = 1;

  /**
   * Key for bug report feedback type.
   */
  const TYPE_BUG = 2;

  /**
   * Gets the website feedback title.
   *
   * @return string
   *   Title of the website feedback.
   */
  public function getTitle();

  /**
   * Sets the website feedback title.
   *
   * @param string $title
   *   The website feedback title.
   *
   * @return \Drupal\website_feedback\WebsiteFeedbackInterface
   *   The called website feedback entity.
   */
  public function setTitle($title);

  /**
   * Gets the website feedback creation timestamp.
   *
   * @return int
   *   Creation timestamp of the website feedback.
   */
  public function getCreatedTime();

  /**
   * Sets the website feedback creation timestamp.
   *
   * @param int $timestamp
   *   The website feedback creation timestamp.
   *
   * @return \Drupal\website_feedback\WebsiteFeedbackInterface
   *   The called website feedback entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the website feedback status.
   *
   * @return bool
   *   TRUE if the website feedback is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the website feedback status.
   *
   * @param bool $status
   *   TRUE to enable this website feedback, FALSE to disable.
   *
   * @return \Drupal\website_feedback\WebsiteFeedbackInterface
   *   The called website feedback entity.
   */
  public function setStatus($status);

}
