<?php

namespace Drupal\commerce_product_test\EventSubscriber;

use Drupal\commerce_product\Event\ProductDefaultVariationEvent;
use Drupal\commerce_product\Event\ProductEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DefaultVariationSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ProductEvents::PRODUCT_DEFAULT_VARIATION => 'onDefaultVariation',
    ];
  }

  /**
   * Handle the default variation event.
   *
   * @param \Drupal\commerce_product\Event\ProductDefaultVariationEvent $event
   *   The event.
   */
  public function onDefaultVariation(ProductDefaultVariationEvent $event) {
    if ($event->getDefaultVariation()->getSku() === 'TEST_DEFAULT_VARIATION_EVENT') {
      $variations = $event->getProduct()->getVariations();
      $new_default = end($variations);
      $event->setDefaultVariation($new_default);
    }
  }

}
