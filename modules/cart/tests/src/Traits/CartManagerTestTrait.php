<?php

namespace Drupal\Tests\commerce_cart\Traits;

trait CartManagerTestTrait {

  /**
   * Installs commerce cart.
   *
   * Due to issues with hook_entity_bundle_create, we need to run this manually
   * and cannot add commerce_cart to the $modules property.
   *
   * @todo patch core so it doesn't explode in Kernel tests.
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
