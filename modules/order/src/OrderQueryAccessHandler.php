<?php

namespace Drupal\commerce_order;

use Drupal\Core\Session\AccountInterface;
use Drupal\entity\QueryAccess\ConditionGroup;
use Drupal\entity\QueryAccess\QueryAccessHandlerBase;

/**
 * Controls query access based on the Order entity permissions.
 *
 * @see \Drupal\commerce_order\OrderAccessControlHandler
 * @see \Drupal\commerce_order\OrderPermissionProvider
 */
class OrderQueryAccessHandler extends QueryAccessHandlerBase {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityConditions($operation, AccountInterface $account) {
    // Orders don't implement EntityOwnerInterface, but they do have a
    // "view own" permission.
    if ($operation == 'view') {
      $conditions = new ConditionGroup('OR');
      $conditions->addCacheContexts(['user.permissions']);
      // The $entity_type permission.
      if ($account->hasPermission('view commerce_order')) {
        // The user has full access, no conditions needed.
        return $conditions;
      }

      // Own $entity_type permission.
      if ($account->hasPermission('view own commerce_order')) {
        $conditions->addCacheContexts(['user']);
        $conditions->addCondition('uid', $account->id());
      }

      $bundles = array_keys($this->bundleInfo->getBundleInfo('commerce_order'));
      $bundles_with_any_permission = [];
      foreach ($bundles as $bundle) {
        if ($account->hasPermission("view $bundle commerce_order")) {
          $bundles_with_any_permission[] = $bundle;
        }
      }
      // The $bundle permission.
      if ($bundles_with_any_permission) {
        $conditions->addCondition('type', $bundles_with_any_permission);
      }

      return $conditions->count() ? $conditions : NULL;
    }

    return parent::buildEntityConditions($operation, $account);
  }

}
