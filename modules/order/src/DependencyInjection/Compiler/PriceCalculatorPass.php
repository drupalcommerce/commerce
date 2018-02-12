<?php

namespace Drupal\commerce_order\DependencyInjection\Compiler;

use Drupal\commerce_order\OrderProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds order processors to the PriceCalculator, grouped by adjustment type.
 */
class PriceCalculatorPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    $definition = $container->getDefinition('commerce_order.price_calculator');
    $processor_interface = OrderProcessorInterface::class;
    $processors = [];
    foreach ($container->findTaggedServiceIds('commerce_order.order_processor') as $id => $attributes) {
      $processor = $container->getDefinition($id);
      if (!is_subclass_of($processor->getClass(), $processor_interface)) {
        throw new LogicException("Service '$id' does not implement $processor_interface.");
      }
      $attribute = $attributes[0];
      if (empty($attribute['adjustment_type'])) {
        continue;
      }

      $processors[$id] = [
        'priority' => isset($attribute['priority']) ? $attribute['priority'] : 0,
        'adjustment_type' => $attribute['adjustment_type'],
      ];
    }

    // Sort the processors by priority.
    uasort($processors, function ($processor1, $processor2) {
      if ($processor1['priority'] == $processor2['priority']) {
        return 0;
      }
      return ($processor1['priority'] > $processor2['priority']) ? -1 : 1;
    });

    // Add the processors to PriceCalculator.
    foreach ($processors as $id => $processor) {
      $arguments = [new Reference($id), $processor['adjustment_type']];
      $definition->addMethodCall('addProcessor', $arguments);
    }
  }

}
