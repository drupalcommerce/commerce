<?php

namespace Drupal\commerce_order\Access;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

class OrderUserViewAccessCheck implements AccessInterface {

  /**
   * Checks access to an order's user view mode.
   *
   * Draft orders are always denied as they have not yet been placed. Otherwise
   * access is delegated to entity access checks.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   */
  public function checkAccess(RouteMatchInterface $route_match, AccountInterface $account) {
    $order = $route_match->getParameter('commerce_order');
    if (!$order instanceof OrderInterface) {
      return AccessResult::neutral();
    }
    if ($order->getState()->getId() === 'draft') {
      return AccessResult::forbidden()->addCacheableDependency($order);
    }

    return $order->access('view', $account, TRUE);
  }

}
