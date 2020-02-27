<?php

namespace Drupal\Tests\commerce_cart\Traits;

/**
 * @deprecated in commerce:8.x-2.17 and is removed from commerce:3.x.
 *   Use CartKernelTestBase instead.
 */
trait CartManagerTestTrait {

  /**
   * Installs commerce cart.
   */
  protected function installCommerceCart() {
    $this->enableModules(['commerce_cart']);
    $this->installConfig('commerce_cart');

    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('commerce_order');
    $cart_field_definition = commerce_cart_entity_base_field_info($entity_type)['cart'];
    \Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition('cart', 'commerce_order', 'commerce_cart', $cart_field_definition);
    $this->cartProvider = $this->container->get('commerce_cart.cart_provider');
    $this->cartManager = $this->container->get('commerce_cart.cart_manager');
  }

}
