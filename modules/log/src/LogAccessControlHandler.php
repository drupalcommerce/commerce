<?php

namespace Drupal\commerce_log;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler as CoreEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an access control handler for logs.
 *
 * Logs are internal entities, always managed and viewed in the context
 * of their source entity. The source entity access is used when possible:
 * - A log can be viewed if the source entity can be viewed.
 * - A log can be updated or deleted if the source entity can be updated.
 *
 * Note: There are currently no limitations imposed on log creation.
 */
class LogAccessControlHandler extends CoreEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    /** @var \Drupal\commerce_log\Entity\LogInterface $entity */
    $source_entity = $entity->getSourceEntity();
    if (!$source_entity) {
      // The log is malformed.
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }
    $parent_operation = ($operation == 'view') ? 'view' : 'update';
    $result = $source_entity->access($parent_operation, $account, TRUE);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowed();
  }

}
