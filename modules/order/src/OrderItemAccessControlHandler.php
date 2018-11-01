<?php

namespace Drupal\commerce_order;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler as CoreEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an access control handler for order items.
 *
 * Order items are always managed in the scope of their parent (the order),
 * so they have a simplified permission set, and rely on parent access
 * when possible:
 * - An order item can be viewed if the parent order can be viewed.
 * - An order item can be created, updated or deleted if the user has the
 *   "manage $bundle commerce_order_item" permission.
 *
 * The "administer commerce_order" permission is also respected.
 */
class OrderItemAccessControlHandler extends CoreEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $entity */
    $order = $entity->getOrder();
    if (!$order) {
      // The order item is malformed.
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }

    if ($operation == 'view') {
      $result = $order->access('view', $account, TRUE);
    }
    else {
      $bundle = $entity->bundle();
      $result = AccessResult::allowedIfHasPermission($account, "manage $bundle commerce_order_item");
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Create access depends on the "manage" permission because the full entity
    // is not passed, making it impossible to determine the parent order.
    $result = AccessResult::allowedIfHasPermissions($account, [
      $this->entityType->getAdminPermission(),
      "manage $entity_bundle commerce_order_item",
    ], 'OR');

    return $result;
  }

}
