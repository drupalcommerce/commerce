<?php

namespace Drupal\commerce;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\BundleEntityAccessControlHandler;

/**
 * Defines the access control handler for bundles.
 */
class CommerceBundleAccessControlHandler extends BundleEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'delete' && $entity->isLocked()) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }
    else {
      return parent::checkAccess($entity, $operation, $account);
    }
  }

}
