<?php

namespace Drupal\Tests\commerce_cart\Kernel;

trait CartManagerTestTrait {

  /**
   * Install commerce cart.
   *
   * Due to issues with hook_entity_bundle_create, we need to run this manually
   * and cannot add commerce_cart to the $modules property.
   *
   * @see https://www.drupal.org/node/2711645
   *
   * @todo patch core so it doesn't explode in Kernel tests.
   */
  protected function installCommerceCart() {
    $this->enableModules(['commerce_cart']);
    $this->installConfig('commerce_cart');
    $this->container->get('entity.definition_update_manager')->applyUpdates();
    $this->cartProvider = $this->container->get('commerce_cart.cart_provider');
    $this->cartManager = $this->container->get('commerce_cart.cart_manager');
  }

}
