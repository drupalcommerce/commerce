<?php

namespace Drupal\commerce_number_pattern;

use Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\SequentialNumberPatternInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for number patterns.
 */
class NumberPatternAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Resetting a sequence requires the same permissions as 'update', with an
    // additional check to ensure that the plugin supports the operation.
    if ($operation == 'reset_sequence') {
      /** @var \Drupal\commerce_number_pattern\Entity\NumberPatternInterface $entity */
      if (!($entity->getPlugin() instanceof SequentialNumberPatternInterface)) {
        return AccessResult::forbidden()->addCacheableDependency($entity);
      }
      $operation = 'update';
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
