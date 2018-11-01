<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class PriceSplitter implements PriceSplitterInterface {

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * The rounder.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * Constructs a new PriceSplitter object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The rounder.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RounderInterface $rounder) {
    $this->currencyStorage = $entity_type_manager->getStorage('commerce_currency');
    $this->rounder = $rounder;
  }

  /**
   * {@inheritdoc}
   */
  public function split(OrderInterface $order, Price $amount, $percentage = NULL) {
    if (!$percentage) {
      // The percentage is intentionally not rounded, for maximum precision.
      $percentage = Calculator::divide($amount->getNumber(), $order->getSubtotalPrice()->getNumber());
    }

    // Calculate the initial per-order-item amounts using the percentage.
    // Round down to ensure that their sum isn't larger than the full amount.
    $amounts = [];
    foreach ($order->getItems() as $order_item) {
      if (!$order_item->getTotalPrice()->isZero()) {
        $individual_amount = $order_item->getTotalPrice()->multiply($percentage);
        $individual_amount = $this->rounder->round($individual_amount, PHP_ROUND_HALF_DOWN);
        // Due to rounding it is possible for the last calculated
        // per-order-item amount to be larger than the total remaining amount.
        if ($individual_amount->greaterThan($amount)) {
          $individual_amount = $amount;
        }
        $amounts[$order_item->id()] = $individual_amount;

        $amount = $amount->subtract($individual_amount);
      }
    }

    // The individual amounts don't add up to the full amount, distribute
    // the reminder among them.
    if (!$amount->isZero()) {
      /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency */
      $currency = $this->currencyStorage->load($amount->getCurrencyCode());
      $precision = $currency->getFractionDigits();
      // Use the smallest rounded currency amount (e.g. '0.01' for USD).
      $smallest_number = Calculator::divide('1', pow(10, $precision), $precision);
      $smallest_amount = new Price($smallest_number, $amount->getCurrencyCode());
      while (!$amount->isZero()) {
        foreach ($amounts as $order_item_id => $individual_amount) {
          $amounts[$order_item_id] = $individual_amount->add($smallest_amount);
          $amount = $amount->subtract($smallest_amount);
          if ($amount->isZero()) {
            break 2;
          }
        }
      }
    }

    return $amounts;
  }

}
