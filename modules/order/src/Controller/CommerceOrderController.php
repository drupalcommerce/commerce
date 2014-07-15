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
    $order_types = $this->entityManager()->getStorage('commerce_order_type')->loadMultiple();
    // Filter out the order types the user doesn't have access to.
    foreach ($order_types as $order_type_id => $order_type) {
      if (!$this->entityManager()->getAccessController('commerce_order')->createAccess($order_type_id)) {
        unset($order_types[$order_type_id]);
      }
    }

    if (count($order_types) == 1) {
      $order_type = reset($order_types);
      return $this->redirect('entity.commerce_order.add', array('commerce_order_type' => $order_type->id()));
    }

    return array(
      '#theme' => 'commerce_order_add_list',
      '#types' => $order_types,
    );
  }

  /**
   * Provides the order add form.
   *
   * @param \Drupal\commerce_order\CommerceOrderTypeInterface $commerce_order_type
   *   The order type entity for the order.
   *
   * @return array
   *   An order add form.
   */
  public function add(CommerceOrderTypeInterface $commerce_order_type) {
    $order = $this->entityManager()->getStorage('commerce_order')->create(array(
      'type' => $commerce_order_type->id(),
    ));
    $form = $this->entityFormBuilder()->getForm($order, 'add');

    return $form;
  }

  /**
   * The title_callback for the commerce_order.add route.
   *
   * @param \Drupal\commerce_order\CommerceOrderTypeInterface $commerce_order_type
   *   The current order type.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(CommerceOrderTypeInterface $commerce_order_type) {
    return $this->t('Create @label', array('@label' => $commerce_order_type->label()));
  }
}
