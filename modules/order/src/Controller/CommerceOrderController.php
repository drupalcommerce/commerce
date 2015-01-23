<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Controller\CommerceOrderController.
 */

namespace Drupal\commerce_order\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_order\CommerceOrderTypeInterface;

/**
 * Returns responses for Commerce Order admin routes.
 */
class CommerceOrderController extends ControllerBase {

  /**
   * Displays add content links for available order types.
   *
   * Redirects to admin/commerce/config/orders/add/{order-type} if only one
   * type is available.
   *
   * @return array
   *   A render array for a list of the order types that can be added.
   */
  public function addPage() {
    $orderTypes = $this->entityManager()->getStorage('commerce_order_type')->loadMultiple();
    // Filter out the order types the user doesn't have access to.
    foreach ($orderTypes as $orderTypeId => $orderType) {
      if (!$this->entityManager()->getAccessControlHandler('commerce_order')->createAccess($orderTypeId)) {
        unset($orderTypes[$orderTypeId]);
      }
    }

    if (count($orderTypes) == 1) {
      $orderType = reset($orderTypes);
      return $this->redirect('entity.commerce_order.add_form', array('commerce_order_type' => $orderType->id()));
    }

    return array(
      '#theme' => 'commerce_order_add_list',
      '#types' => $orderTypes,
    );
  }

  /**
   * Provides the order add form.
   *
   * @param \Drupal\commerce_order\CommerceOrderTypeInterface $commerceOrderType
   *   The order type entity for the order.
   *
   * @return array
   *   An order add form.
   */
  public function add(CommerceOrderTypeInterface $commerceOrderType) {
    $order = $this->entityManager()->getStorage('commerce_order')->create(array(
      'type' => $commerceOrderType->id(),
    ));
    $form = $this->entityFormBuilder()->getForm($order, 'add');

    return $form;
  }

  /**
   * The title_callback for the entity.commerce_order.add_form route.
   *
   * @param \Drupal\commerce_order\CommerceOrderTypeInterface $commerceOrderType
   *   The current order type.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(CommerceOrderTypeInterface $commerceOrderType) {
    return $this->t('Create @label', array('@label' => $commerceOrderType->label()));
  }

}
