<?php

/**
 * @file
 * Contains \Drupal\commerce_payment\Controller\CommercePaymentInfoDevelController.
 */

namespace Drupal\commerce_payment\Controller;

use Drupal\commerce_payment\CommercePaymentInfoInterface;
use Drupal\commerce_payment\CommercePaymentInfoTypeInterface;
use Drupal\devel\Controller\DevelController;

/**
 * Returns responses for Commerce payment information devel routes.
 */
class CommercePaymentInfoDevelController extends DevelController {

  /**
   * Dump devel information for a Commerce payment information type.
   *
   * @param \Drupal\commerce_payment\CommercePaymentInfoTypeInterface $payment_info_type
   *
   * @return string
   */
  public function paymentInfoTypeLoad(CommercePaymentInfoTypeInterface $payment_info_type) {
    return $this->loadObject('commerce_payment_info_type', $payment_info_type);
  }

  /**
   * Dump devel information for a Commerce payment information.
   *
   * @param \Drupal\commerce_payment\CommercePaymentInfoInterface $payment_info
   *
   * @return string
   */
  public function paymentInfoLoad(CommercePaymentInfoInterface $payment_info) {
    return $this->loadObject('commerce_payment_info', $payment_info);
  }

}
