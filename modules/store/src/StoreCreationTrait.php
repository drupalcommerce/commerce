<?php

namespace Drupal\commerce_store;

use Drupal\commerce_store\Entity\Store;

/**
 * Provides methods to create stores and set the default store.
 *
 * This trait is meant to be used only by test classes.
 *
 * @todo Move to \Drupal\Tests\commerce_store post-SimpleTest.
 */
trait StoreCreationTrait {

  /**
   * Creates a store for the test.
   *
   * @param string $name
   *   The store name.
   * @param string $mail
   *   The store email.
   * @param string $type
   *   The store type.
   * @param bool $default
   *   Whether the store should be the default store.
   * @param string $country
   *   The store country code.
   * @param string $currency
   *   The store currency code.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface
   *   The store.
   */
  protected function createStore($name = NULL, $mail = NULL, $type = 'online', $default = TRUE, $country = 'US', $currency = 'USD') {
    if (!$name) {
      $name = $this->randomMachineName(8);
    }
    if (!$mail) {
      $mail = \Drupal::currentUser()->getEmail();
    }

    $currency_importer = \Drupal::service('commerce_price.currency_importer');
    $currency_importer->import($currency);
    $store = Store::create([
      'type' => $type,
      'uid' => 1,
      'name' => $name,
      'mail' => $mail,
      'address' => [
        'country_code' => $country,
        'address_line1' => $this->randomString(),
        'locality' => $this->randomString(5),
        'administrative_area' => 'WI',
        'postal_code' => '53597',
      ],
      'default_currency' => $currency,
      'billing_countries' => [
        $country,
      ],
    ]);
    $store->save();

    if ($default) {
      /** @var \Drupal\commerce_store\StoreStorage $store_storage */
      $store_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_store');
      $store_storage->markAsDefault($store);
    }

    $store = Store::load($store->id());

    return $store;
  }

}
