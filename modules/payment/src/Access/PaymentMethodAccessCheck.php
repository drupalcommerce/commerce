<?php

namespace Drupal\commerce_payment\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for payment method routes.
 *
 * @see \Drupal\Core\Access\CustomAccessCheck
 */
class PaymentMethodAccessCheck {

  /**
   * Checks access.
   *
   * Confirms that the user either has the 'administer commerce_payment_method'
   * permission, or the 'manage own commerce_payment_method' permission while
   * visiting their own payment method pages.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(RouteMatchInterface $route_match, AccountInterface $account) {
    $result = AccessResult::allowedIfHasPermissions($account, [
      'administer commerce_payment_method',
    ]);

    $current_user = $route_match->getParameter('user');
    if ($result->isNeutral() && $current_user->id() == $account->id()) {
      $result = AccessResult::allowedIfHasPermissions($account, [
        'manage own commerce_payment_method',
      ])->cachePerUser();
    }

    return $result;
  }

}
