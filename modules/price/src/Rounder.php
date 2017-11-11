<?php

namespace Drupal\commerce_price;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class Rounder implements RounderInterface {

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * Constructs a new Rounder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->currencyStorage = $entity_type_manager->getStorage('commerce_currency');
  }

  /**
   * {@inheritdoc}
   */
  public function round(Price $price, $mode = PHP_ROUND_HALF_UP) {
    $currency_code = $price->getCurrencyCode();
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency */
    $currency = $this->currencyStorage->load($currency_code);
    if (!$currency) {
      throw new \InvalidArgumentException(sprintf('Could not load the "%s" currency.', $currency_code));
    }
    $rounded_number = Calculator::round($price->getNumber(), $currency->getFractionDigits(), $mode);

    return new Price($rounded_number, $currency_code);
  }

}
