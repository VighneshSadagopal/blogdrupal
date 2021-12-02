<?php

namespace Drupal\website_feedback;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the website feedback entity type.
 */
class WebsiteFeedbackAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view website feedback');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, [
          'edit website feedback',
          'administer website feedback',
        ], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, [
          'delete website feedback',
          'administer website feedback',
        ], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      'create website feedback',
      'administer website feedback',
    ], 'OR');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    // Only users with the edit and administer website feedback permission can
    // edit administrative fields.
    $administrative_fields = ['uid', 'status', 'created'];
    if ($operation == 'edit' && in_array($field_definition->getName(), $administrative_fields, TRUE)) {
      return AccessResult::allowedIfHasPermissions($account, [
        'edit website feedback',
        'administer website feedback',
      ], 'OR');
    }
    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

}
