<?php

namespace Drupal\commerce_price_test;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\Resolver\PriceResolverInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Test multicurrency price resolver.
 */
class TestMulticurrencyPriceResolver implements PriceResolverInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new CommerceMulticurrencyResolver object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    // Define mapping between language and currency.
    $currency_by_language = ['fr' => 'EUR'];

    // Get current language.
    $language = $this->languageManager->getCurrentLanguage()->getId();

    // Get value from currency price field, if exists.
    if (isset($currency_by_language[$language]) && $entity->hasField('price_' . strtolower($currency_by_language[$language]))) {
      $price = $entity->get('price_' . strtolower($currency_by_language[$language]))->getValue();
      $price = reset($price);

      return new Price($price['number'], $price['currency_code']);
    }
  }

}
