<?php

namespace Drupal\commerce_price;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Swaps the default physical.number_formatter service class.
 */
class CommercePriceServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('physical.number_formatter')) {
      $container->getDefinition('physical.number_formatter')
        ->setClass(PhysicalNumberFormatter::class)
        ->setArguments([new Reference('commerce_price.number_format_repository'), new Reference('commerce.current_locale')]);
    }
  }

}
