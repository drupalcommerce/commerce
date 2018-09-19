<?php

namespace Drupal\commerce_order;

use Drupal\commerce\AvailabilityManagerInterface;
use Drupal\commerce\Context;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;

/**
 * Provides an order processor that removes entities that are no longer available.
 */
class AvailabilityOrderProcessor implements OrderProcessorInterface {

  /**
   * The availability manager.
   *
   * @var \Drupal\commerce\AvailabilityManagerInterface
   */
  protected $availabilityManager;

  /**
   * Constructs a new AvailabilityOrderProcessor object.
   *
   * @param \Drupal\commerce\AvailabilityManagerInterface $availability_manager
   *   The availability manager.
   */
  public function __construct(AvailabilityManagerInterface $availability_manager) {
    $this->availabilityManager = $availability_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    // @todo Get $context as an argument to process().
    $context = new Context($order->getCustomer(), $order->getStore());
    foreach ($order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      if ($purchased_entity) {
        /** @var \Drupal\commerce\AvailabilityResponseInterface $availability */
        $availability = $this->availabilityManager->check($purchased_entity, $order_item->getQuantity(), $context);
        if (!$availability->isNeutral()) {
          if ($availability->getMax() == 0) {
            $order->removeItem($order_item);
            $order_item->delete();
            drupal_set_message(t('The item %item is no longer available and has been removed from your cart.', [
              '%item' => $order_item->getTitle(),
            ]), 'warning');
          }
          elseif ($availability->getMax() < $order_item->getQuantity()) {
            drupal_set_message(new PluralTranslatableMarkup(abs($availability->getMax() - $order_item->getQuantity()), '1 %item is currently unavailable. The quantity in your cart has been updated.', '@count %item are currently unavailable. The quantity in your cart has been updated.', [
              '%item' => $order_item->getTitle(),
            ]), 'warning');
            $order_item->setQuantity($availability->getMax());
            $order_item->save();
          }
        }
      }
    }
  }

}
