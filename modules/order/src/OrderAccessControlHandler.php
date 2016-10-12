<?php

namespace Drupal\commerce_order;

use Drupal\commerce\EntityAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Controls access based on the Order entity permissions.
 */
class OrderAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $account = $this->prepareUser($account);
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::checkAccess($entity, $operation, $account);

    if ($result->isNeutral() && $operation == 'view') {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $entity */
      if ($account->id() == $entity->getCustomerId()) {
        $result = AccessResult::allowedIfHasPermissions($account, ['view own commerce_order']);
        $result = $result->cachePerUser()->addCacheableDependency($entity);
      }
    }

    return $result;
  }

}
