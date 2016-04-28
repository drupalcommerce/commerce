<?php

namespace Drupal\commerce_checkout;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class CheckoutOrderManager implements CheckoutOrderManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CheckoutOrderManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getCheckoutFlow(OrderInterface $order) {
    if ($order->checkout_flow->isEmpty()) {
      /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
      $order_type = $this->entityTypeManager->getStorage('commerce_order_type')->load($order->bundle());
      $checkout_flow = $order_type->getThirdPartySetting('commerce_checkout', 'checkout_flow', 'default');
      // @todo Allow other modules to add their own resolving logic.
      $order->checkout_flow->target_id = $checkout_flow;
      $order->save();
    }

    return $order->checkout_flow->entity;
  }

}
