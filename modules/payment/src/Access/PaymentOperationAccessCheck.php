<?php

namespace Drupal\commerce_payment\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an access checker for payment operations.
 */
class PaymentOperationAccessCheck implements AccessInterface {

  /**
   * Checks access to the payment operation on the given route.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    $entity = $route_match->getParameter('commerce_payment');
    $operation = $route_match->getParameter('operation');
    if (empty($entity) || empty($operation)) {
      return AccessResult::neutral();
    }
    return $entity->access($operation, $account, TRUE);
  }

}
