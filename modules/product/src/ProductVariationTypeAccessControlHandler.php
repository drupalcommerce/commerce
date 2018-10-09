<?php

namespace Drupal\commerce_product;

use Drupal\commerce\CommerceBundleAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Controls access to product variation type entities.
 *
 * Allows the product variation type entity label to be viewed if a collection
 * of product variations of that type can be viewed.
 *
 * @see \Drupal\commerce_product\Access\ProductVariationCollectionAccessCheck
 */
class ProductVariationTypeAccessControlHandler extends CommerceBundleAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected $viewLabelOperation = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'view label') {
      $bundle = $entity->id();
      $permissions = [
        'administer commerce_product',
        'access commerce_product overview',
        "manage $bundle commerce_product_variation",
      ];

      return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
    }
    else {
      return parent::checkAccess($entity, $operation, $account);
    }
  }

}
