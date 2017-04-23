<?php

namespace Drupal\commerce_product;

use Drupal\commerce\EntityAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Controls access based on the Product entity permissions.
 */
class ProductAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $entity */
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::checkAccess($entity, $operation, $account);

    if ($result->isNeutral() && $entity->isPublished()) {
      $result = AccessResult::allowedIfHasPermission($account, 'view published ' . $entity->getEntityTypeId());
      $result->addCacheableDependency($entity);
    }

    return $result;
  }

}
