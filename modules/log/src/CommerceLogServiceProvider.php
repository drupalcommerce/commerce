<?php

namespace Drupal\commerce_log;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers event subscribers for installed Commerce modules.
 */
class CommerceLogServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // We cannot use the module handler as the container is not yet compiled.
    // @see \Drupal\Core\DrupalKernel::compileContainer()
    $modules = $container->getParameter('container.modules');

    if (isset($modules['commerce_cart'])) {
      $container->register('commerce_log.cart_subscriber', 'Drupal\commerce_log\EventSubscriber\CartEventSubscriber')
        ->addTag('event_subscriber')
        ->addArgument(new Reference('entity_type.manager'));
    }
    if (isset($modules['commerce_order'])) {
      $container->register('commerce_log.order_subscriber', 'Drupal\commerce_log\EventSubscriber\OrderEventSubscriber')
        ->addTag('event_subscriber')
        ->addArgument(new Reference('entity_type.manager'));

      $container->register('commerce_log.order_mail_subscriber', 'Drupal\commerce_log\EventSubscriber\OrderMailEventSubscriber')
        ->addTag('event_subscriber')
        ->addArgument(new Reference('entity_type.manager'))
        ->addArgument(new Reference('plugin.manager.commerce_log_template'));
    }
  }

}
