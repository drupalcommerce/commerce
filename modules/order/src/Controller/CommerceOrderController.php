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
   * type is available. however, if there is only one order type defined for
   * the site, the function redirects to the order add page for that type.
   *
   * @return array
   *   A render array for a list of the order types that can be added.
   */
  public function addPage() {
    $types = array();

    // Only use order types the user has access to.
    foreach ($this->entityManager()->getStorage('commerce_order_type')->loadMultiple() as $order_type) {
      if ($this->entityManager()->getAccessController('commerce_order')->createAccess($order_type->id)) {
        $types[$order_type->id] = $order_type;
      }
    }

    if (count($types) == 1) {
      $type = array_shift($types);
      return $this->redirect('commerce_order.add', array('commerce_order_type' => $type->id));
    }

    return array(
      '#theme' => 'commerce_order_add_list',
      '#types' => $types,
    );
  }

  /**
   * Provides the order add form.
   *
   * @param \Drupal\commerce_order\CommerceOrderTypeInterface $order_type
   *   The order type entity for the order.
   *
   * @return array
   *   An order add form.
   */
  public function add(CommerceOrderTypeInterface $order_type) {
    $order = $this->entityManager()->getStorage('commerce_order')->create(array(
      'type' => $order_type->id,
    ));

    $form = $this->entityFormBuilder()->getForm($order, 'add');

    return $form;
  }

  /**
   * The title_callback for the commerce_order.add route.
   *
   * @param \Drupal\commerce_order\CommerceOrderTypeInterface $order_type
   *   The current order type.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(CommerceOrderTypeInterface $order_type) {
    return $this->t('Create @label', array('@label' => $order_type->label()));
  }
}
