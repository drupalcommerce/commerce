<?php

/**
 * @file
 * Contains \Drupal\commerce\CommerceOrderTypeAccessController.
 */

namespace Drupal\commerce_order;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a Commerce Order Type access controller.
 */
class CommerceOrderTypeAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {

    if ($operation === 'delete') {
      /** @var $entity \Drupal\commerce\CommerceOrderTypeInterface */
      $count = $entity->getOrderCount();
      if ($count > 0) {
        return FALSE;
      }
    }

    /** @var $entity \Drupal\commerce\CommerceOrderTypeInterface */
    return parent::checkAccess($entity, $operation, $langcode, $account);
  }

}
