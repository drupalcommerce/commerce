<?php

namespace Drupal\commerce_promotion;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Controls coupon access based on the parent promotion.
 */
class CouponAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::checkAccess($entity, $operation, $account);

    if ($result->isNeutral() && $promotion = $entity->getPromotion()) {
      /** @var \Drupal\commerce_promotion\Entity\CouponInterface $entity */
      $result = $promotion->access('update', $account, TRUE);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer commerce_promotion',
      'update commerce_promotion',
    ], 'OR');
  }

}
