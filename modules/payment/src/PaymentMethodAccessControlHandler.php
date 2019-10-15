<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsUpdatingStoredPaymentMethodsInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for payment methods.
 */
class PaymentMethodAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $entity */
    if ($operation == 'update') {
      $payment_gateway = $entity->getPaymentGateway();
      // Deny access if the gateway is missing or doesn't support updates.
      if (!$payment_gateway) {
        return AccessResult::forbidden()->addCacheableDependency($entity);
      }
      if (!($payment_gateway->getPlugin() instanceof SupportsUpdatingStoredPaymentMethodsInterface)) {
        return AccessResult::forbidden()->addCacheableDependency($entity);
      }
    }

    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $result = AccessResult::allowedIf($account->id() == $entity->getOwnerId())
      ->andIf(AccessResult::allowedIfHasPermission($account, 'manage own commerce_payment_method'))
      ->addCacheableDependency($entity)
      ->cachePerUser();

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      $this->entityType->getAdminPermission(),
      'manage own commerce_payment_method',
    ], 'OR');
  }

}
