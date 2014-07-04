<?php

/**
 * @file
 * Contains \Drupal\commerce_product\CommerceProductTypeAccessController.
 */

namespace Drupal\commerce_product;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a Commerce Product Type access controller.
 */
class CommerceProductTypeAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {

    if ($operation === 'delete') {
      /** @var $entity \Drupal\commerce\CommerceProductTypeInterface */
      $count = $entity->getProductCount();
      if ($count > 0) {
        return FALSE;
      }
    }

    /** @var $entity \Drupal\commerce\CommerceProductTypeInterface */
    return parent::checkAccess($entity, $operation, $langcode, $account);
  }

}
