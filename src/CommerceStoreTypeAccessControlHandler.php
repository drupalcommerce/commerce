<?php

/**
 * @file
 * Contains \Drupal\commerce\CommerceStoreTypeAccessControlHandler.
 */

namespace Drupal\commerce;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a Commerce Store Type access control handler.
 */
class CommerceStoreTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {

    if ($operation === 'delete') {
      /** @var $entity \Drupal\commerce\CommerceStoreTypeInterface */
      $count = $entity->getStoreCount();
      if ($count > 0) {
        return FALSE;
      }
    }

    /** @var $entity \Drupal\commerce\CommerceStoreTypeInterface */
    return parent::checkAccess($entity, $operation, $langcode, $account);
  }

}
