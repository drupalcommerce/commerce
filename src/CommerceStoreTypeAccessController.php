<?php

/**
 * @file
 * Contains \Drupal\commerce_type\CommerceStoreTypeAccessController.
 */

namespace Drupal\commerce;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a Commerce Store Type access controller.
 */
class CommerceStoreTypeAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {

    if ($operation === 'delete') {
      /** @var $entity \Drupal\commerce\CommerceStoreTypeInterface */
      $count = $entity->getStoreCount();
      if ($count > 0) {
        return false;
      }
    }

    /** @var $entity \Drupal\commerce\CommerceStoreTypeInterface */
    return parent::checkAccess($entity, $operation, $langcode, $account);
  }

}
