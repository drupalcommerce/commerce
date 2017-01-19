<?php

namespace Drupal\commerce_checkout\Resolver;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Returns the order type's default checkout flow.
 */
class DefaultCheckoutFlowResolver implements CheckoutFlowResolverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new DefaultCheckoutFlowResolver object.
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
  public function resolve(OrderInterface $order) {
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $this->entityTypeManager->getStorage('commerce_order_type')->load($order->bundle());
    $checkout_flow_id = $order_type->getThirdPartySetting('commerce_checkout', 'checkout_flow', 'default');
    $checkout_flow = $this->entityTypeManager->getStorage('commerce_checkout_flow')->load($checkout_flow_id);
    return $checkout_flow;
  }

}
