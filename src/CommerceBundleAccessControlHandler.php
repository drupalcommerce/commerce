<?php

namespace Drupal\commerce;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler as CoreEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for bundles.
 */
class CommerceBundleAccessControlHandler extends CoreEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\commerce\Entity\CommerceBundleEntityInterface $entity */
    $admin_permission = $entity->getEntityType()->getAdminPermission();
    if ($operation === 'delete') {
      if ($entity->isLocked()) {
        return AccessResult::forbidden()->addCacheableDependency($entity);
      }
      else {
        return AccessResult::allowedIfHasPermission($account, $admin_permission)->addCacheableDependency($entity);
      }
    }
    return AccessResult::allowedIfHasPermission($account, $admin_permission);
  }

}
