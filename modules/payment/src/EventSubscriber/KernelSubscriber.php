<?php

namespace Drupal\commerce_payment\EventSubscriber;

use Drupal\commerce_payment\PaymentOrderUpdaterInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class KernelSubscriber implements EventSubscriberInterface {

  /**
   * The payment order updater.
   *
   * @var \Drupal\commerce_payment\PaymentOrderUpdaterInterface
   */
  protected $paymentOrderUpdater;

  /**
   * Constructs a new KernelSubscriber instance.
   *
   * @param \Drupal\commerce_payment\PaymentOrderUpdaterInterface $payment_order_updater
   *   The payment order updater.
   */
  public function __construct(PaymentOrderUpdaterInterface $payment_order_updater) {
    $this->paymentOrderUpdater = $payment_order_updater;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::TERMINATE => ['onTerminate', 400],
    ];
  }

  /**
   * Updates all remaining orders with pending updates.
   *
   * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   The event.
   */
  public function onTerminate(PostResponseEvent $event) {
    $this->paymentOrderUpdater->updateOrders();
  }

}
