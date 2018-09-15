<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\DependencyInjection\Compiler\PriceCalculatorPass;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Registers the PriceCalculator compiler pass.
 */
class CommerceOrderServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $container->addCompilerPass(new PriceCalculatorPass());
  }

}
