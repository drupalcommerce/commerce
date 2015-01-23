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
   * @param \Drupal\commerce_payment\CommercePaymentInfoTypeInterface $paymentInfoType
   *
   * @return string
   */
  public function paymentInfoTypeLoad(CommercePaymentInfoTypeInterface $paymentInfoType) {
    return $this->loadObject('commerce_payment_info_type', $paymentInfoType);
  }

  /**
   * Dump devel information for a Commerce payment information.
   *
   * @param \Drupal\commerce_payment\CommercePaymentInfoInterface $paymentInfo
   *
   * @return string
   */
  public function paymentInfoLoad(CommercePaymentInfoInterface $paymentInfo) {
    return $this->loadObject('commerce_payment_info', $paymentInfo);
  }

}
