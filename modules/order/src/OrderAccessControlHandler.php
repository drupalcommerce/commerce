<?php

/**
 * @file Contains \Drupal\commerce_order\OrderAccessControlHandler.
 */

namespace Drupal\commerce_order;


use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the order entity type.
 *
 * @see \Drupal\commerce_order\Entity\Order
 */
class OrderAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    // The store field can be viewed but not modified once saved.
    if ($operation == 'edit' && $items !== NULL) {
      return AccessResult::forbidden();
    }

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }


}