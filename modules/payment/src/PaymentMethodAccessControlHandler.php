<?php

namespace Drupal\commerce_payment;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for payment methods.
 *
 * @see \Drupal\commerce_payment\Entity\PaymentMethod
 */
class PaymentMethodAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $account = $this->prepareUser($account);
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::checkAccess($entity, $operation, $account);

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $entity */
    if ($result->isNeutral() && $account->id() == $entity->getOwnerId()) {
      $result = AccessResult::allowedIfHasPermissions($account, [
        'manage own commerce_payment_method',
      ])->addCacheableDependency($entity)->cachePerUser();
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      $this->entityType->getAdminPermission(),
      'manage own commerce_payment_method',
    ]);
  }

}
