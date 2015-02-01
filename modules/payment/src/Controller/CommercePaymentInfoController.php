<?php

/**
 * @file
 * Contains \Drupal\commerce_payment\Controller\CommercePaymentInfoController.
 */

namespace Drupal\commerce_payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_payment\CommercePaymentInfoTypeInterface;

/**
 * Returns responses for Commerce payment admin routes.
 */
class CommercePaymentInfoController extends ControllerBase {

  /**
   * Displays add content links for available payment information types.
   *
   * Redirects to admin/commerce/config/payment-info/add/{payment-info-type} if
   * only one type is available.
   *
   * @return array
   *   A render array for a list of the payment info types that can be added.
   */
  public function addPage() {
    $paymentInfoTypes = $this->entityManager()->getStorage('commerce_payment_info_type')->loadMultiple();
    // Filter out the payment information types the user doesn't have access to.
    foreach ($paymentInfoTypes as $paymentInfoTypeId => $paymentInfoType) {
      if (!$this->entityManager()->getAccessControlHandler('commerce_payment_info')->createAccess($paymentInfoTypeId)) {
        unset($paymentInfoTypes[$paymentInfoTypeId]);
      }
    }

    if (count($paymentInfoTypes) == 1) {
      $paymentInfoType = reset($paymentInfoTypes);
      return $this->redirect('entity.commerce_payment_info.add_form', array('commerce_payment_info_type' => $paymentInfoType->id()));
    }

    return array(
      '#theme' => 'commerce_payment_info_add_list',
      '#types' => $paymentInfoTypes,
    );
  }

  /**
   * Provides the payment information add form.
   *
   * @param \Drupal\commerce_payment\CommercePaymentInfoTypeInterface $commerce_payment_info_type
   *   The payment information type entity.
   *
   * @return array
   *   An payment information add form.
   */
  public function add(CommercePaymentInfoTypeInterface $commerce_payment_info_type) {
    $commerce_payment_info_type = $this->entityManager()->getStorage('commerce_payment_info')->create(array(
      'payment_method' => $commerce_payment_info_type->id(),
    ));
    $form = $this->entityFormBuilder()->getForm($commerce_payment_info_type, 'add');

    return $form;
  }

  /**
   * The title_callback for the entity.commerce_payment_info.add_form route.
   *
   * @param \Drupal\commerce_payment\CommercePaymentInfoTypeInterface $commerce_payment_info_type
   *   The current payment information type.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(CommercePaymentInfoTypeInterface $commerce_payment_info_type) {
    return $this->t('Create @label', array('@label' => $commerce_payment_info_type->label()));
  }

}
