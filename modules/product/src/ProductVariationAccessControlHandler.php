<?php

namespace Drupal\commerce_product;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler as CoreEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an access control handler for product variations.
 *
 * Product variations are always managed in the scope of their parent
 * (the product), so they have a simplified permission set, and rely on
 * parent access when possible:
 * - A product variation can be viewed if the parent product can be viewed.
 * - A product variation can be created, updated or deleted if the user has the
 *   "manage $bundle commerce_product_variation" permission.
 *
 * The "administer commerce_product" permission is also respected.
 */
class ProductVariationAccessControlHandler extends CoreEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $entity */
    $product = $entity->getProduct();
    if (!$product) {
      // The product variation is malformed.
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }

    if ($operation === 'view') {
      $result = $product->access('view', $account, TRUE);
      assert($result instanceof AccessResult);
      $result->addCacheableDependency($entity);
    }
    else {
      $bundle = $entity->bundle();
      $result = AccessResult::allowedIfHasPermission($account, "manage $bundle commerce_product_variation")->cachePerPermissions();
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Create access depends on the "manage" permission because the full entity
    // is not passed, making it impossible to determine the parent product.
    $result = AccessResult::allowedIfHasPermissions($account, [
      $this->entityType->getAdminPermission(),
      "manage $entity_bundle commerce_product_variation",
    ], 'OR');

    return $result;
  }

}
