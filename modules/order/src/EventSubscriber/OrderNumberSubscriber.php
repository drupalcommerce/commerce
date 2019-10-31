<?php

namespace Drupal\commerce_order\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Generates the order number for placed orders.
 *
 * Modules wishing to provide their own order number logic should register
 * an event subscriber with a higher priority (for example, 0).
 *
 * Modules that need access to the generated order number should register
 * an event subscriber with a lower priority (for example, -50).
 */
class OrderNumberSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new OrderNumberSubscriber object.
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
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.pre_transition' => ['setOrderNumber', -30],
    ];
    return $events;
  }

  /**
   * Sets the order number.
   *
   * The number is generated using the number pattern specified by the
   * order type. If no number pattern was specified, the order ID is
   * used as a fallback.
   *
   * Skipped if the order number has already been set.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function setOrderNumber(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    if (!$order->getOrderNumber()) {
      $order_type_storage = $this->entityTypeManager->getStorage('commerce_order_type');
      /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
      $order_type = $order_type_storage->load($order->bundle());
      /** @var \Drupal\commerce_number_pattern\Entity\NumberPatternInterface $number_pattern */
      $number_pattern = $order_type->getNumberPattern();
      if ($number_pattern) {
        $order_number = $number_pattern->getPlugin()->generate($order);
      }
      else {
        $order_number = $order->id();
      }

      $order->setOrderNumber($order_number);
    }
  }

}
