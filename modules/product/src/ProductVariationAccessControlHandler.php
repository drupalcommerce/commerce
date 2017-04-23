<?php

namespace Drupal\commerce_product;

use Drupal\commerce\EmbeddedEntityAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Controls access based on the Product entity permissions.
 */
class ProductVariationAccessControlHandler extends EmbeddedEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $entity */
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::checkAccess($entity, $operation, $account);

    if ($result->isNeutral()) {
      $result = AccessResult::allowedIf($entity->isActive());
      $result->addCacheableDependency($entity);
    }

    return $result;
  }

}
