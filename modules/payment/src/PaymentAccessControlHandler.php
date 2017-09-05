<?php

namespace Drupal\commerce_payment;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for payments.
 *
 * @see \Drupal\commerce_payment\Entity\Payment
 */
class PaymentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $entity */
    $order = $entity->getOrder();
    $access = AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission())
      ->andIf(AccessResult::allowedIf($order && $order->access('view', $account, TRUE)))
      ->addCacheableDependency($entity);
    if ($operation == 'delete') {
      // @todo Add a payment gateway method for this check,
      // to allow a differently named test mode.
      $access = $access->andIf(AccessResult::allowedIf($entity->getPaymentGatewayMode() == 'test'));
    }
    elseif (!in_array($operation, ['view', 'view label', 'delete'])) {
      $payment_gateway_plugin = $entity->getPaymentGateway()->getPlugin();
      $operations = $payment_gateway_plugin->buildPaymentOperations($entity);
      if (!isset($operations[$operation])) {
        // Invalid operation.
        return AccessResult::neutral();
      }
      $allowed = !empty($operations[$operation]['access']);
      $access = $access->andIf(AccessResult::allowedIf($allowed));
    }

    return $access;
  }

}
