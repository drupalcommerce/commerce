<?php

namespace Drupal\commerce_log\EventSubscriber;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Event\PaymentEvent;
use Drupal\commerce_payment\Event\PaymentEvents;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaymentEventSubscriber implements EventSubscriberInterface {

  /**
   * The log storage.
   *
   * @var \Drupal\commerce_log\LogStorageInterface
   */
  protected $logStorage;

  /**
   * Constructs a new PaymentEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->logStorage = $entity_type_manager->getStorage('commerce_log');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_payment.authorize.pre_transition' => ['onAuthorizeTransition', -100],
      'commerce_payment.void.pre_transition' => ['onVoidTransition', -100],
      'commerce_payment.expire.pre_transition' => ['onExpireTransition', -100],
      'commerce_payment.authorize_capture.pre_transition' => ['onAuthorizeCaptureTransition', -100],
      PaymentEvents::PAYMENT_AUTHORIZED => ['onAuthorize', -100],
      PaymentEvents::PAYMENT_VOIDED => ['onVoid', -100],
      PaymentEvents::PAYMENT_EXPIRED => ['onExpire', -100],
      PaymentEvents::PAYMENT_AUTHORIZED_CAPTURED => ['onAuthorizeCapture', -100],
      PaymentEvents::PAYMENT_PARTIALLY_CAPTURED => ['onPartialCapture', -100],
      PaymentEvents::PAYMENT_CAPTURED => ['onCapture', -100],
      PaymentEvents::PAYMENT_PARTIALLY_REFUNDED => ['onPartialRefund', -100],
      PaymentEvents::PAYMENT_REFUNDED => ['onRefund', -100],
    ];
    return $events;
  }

  /**
   * Creates a log when a payment is authorized.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onAuthorizeTransition(WorkflowTransitionEvent $event) {
    $this->dispatch(PaymentEvents::PAYMENT_AUTHORIZED, $event->getEntity());
  }

  /**
   * Creates a log when a payment is voided.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onVoidTransition(WorkflowTransitionEvent $event) {
    $this->dispatch(PaymentEvents::PAYMENT_VOIDED, $event->getEntity());
  }

  /**
   * Creates a log when a payment is expired.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onExpireTransition(WorkflowTransitionEvent $event) {
    $this->dispatch(PaymentEvents::PAYMENT_EXPIRED, $event->getEntity());
  }

  /**
   * Creates a log when a payment is authorized and captured.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onAuthorizeCaptureTransition(WorkflowTransitionEvent $event) {
    $this->dispatch(PaymentEvents::PAYMENT_AUTHORIZED_CAPTURED, $event->getEntity());
  }

  /**
   * Dispatches a PaymentEvent for a payment.
   *
   * @param string $event_name
   *   A name of the payment event to dispatch.
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   A payment to use for dispatching the event.
   */
  private function dispatch($event_name, PaymentInterface $payment) {
    /** @var \Drupal\commerce_payment\Event\PaymentEvent $event */
    $event = new PaymentEvent($payment);
    /** @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_dispatcher */
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch($event_name, $event);
  }

  /**
   * Creates a log when a payment is authorized.
   *
   * @param \Drupal\commerce_payment\Event\PaymentEvent $event
   *   The payment event.
   */
  public function onAuthorize(PaymentEvent $event) {
    $payment = $event->getPayment();
    $this->logStorage->generate($payment, 'payment_authorized', [
      'payment_remote_id' => $payment->getRemoteId(),
    ])->save();
  }

  /**
   * Creates a log when a payment is voided.
   *
   * @param \Drupal\commerce_payment\Event\PaymentEvent $event
   *   The payment event.
   */
  public function onVoid(PaymentEvent $event) {
    $payment = $event->getPayment();
    $this->logStorage->generate($payment, 'payment_voided', [
      'payment_remote_id' => $payment->getRemoteId(),
    ])->save();
  }

  /**
   * Creates a log when a payment is expired.
   *
   * @param \Drupal\commerce_payment\Event\PaymentEvent $event
   *   The payment event.
   */
  public function onExpire(PaymentEvent $event) {
    $payment = $event->getPayment();
    $this->logStorage->generate($payment, 'payment_expired', [
      'payment_remote_id' => $payment->getRemoteId(),
    ])->save();
  }

  /**
   * Creates a log when a payment is authorized and captured.
   *
   * @param \Drupal\commerce_payment\Event\PaymentEvent $event
   *   The payment event.
   */
  public function onAuthorizeCapture(PaymentEvent $event) {
    $payment = $event->getPayment();
    $this->logStorage->generate($payment, 'payment_authorized_captured', [
      'payment_remote_id' => $payment->getRemoteId(),
    ])->save();
  }

  /**
   * Creates a log when a payment is partially captured.
   *
   * @param \Drupal\commerce_payment\Event\PaymentEvent $event
   *   The payment event.
   */
  public function onPartialCapture(PaymentEvent $event) {
    $payment = $event->getPayment();
    $this->logStorage->generate($payment, 'payment_partially_captured', [
      'payment_remote_id' => $payment->getRemoteId(),
      'captured_amount' => $event->getAmount(),
    ])->save();
  }

  /**
   * Creates a log when a payment is fully captured.
   *
   * @param \Drupal\commerce_payment\Event\PaymentEvent $event
   *   The payment event.
   */
  public function onCapture(PaymentEvent $event) {
    $payment = $event->getPayment();
    $this->logStorage->generate($payment, 'payment_captured', [
      'payment_remote_id' => $payment->getRemoteId(),
    ])->save();
  }

  /**
   * Creates a log when a payment is partially refunded.
   *
   * @param \Drupal\commerce_payment\Event\PaymentEvent $event
   *   The payment event.
   */
  public function onPartialRefund(PaymentEvent $event) {
    $payment = $event->getPayment();
    $this->logStorage->generate($payment, 'payment_partially_refunded', [
      'payment_remote_id' => $payment->getRemoteId(),
      'refunded_amount' => $event->getAmount(),
    ])->save();
  }

  /**
   * Creates a log when a payment is fully refunded.
   *
   * @param \Drupal\commerce_payment\Event\PaymentEvent $event
   *   The payment event.
   */
  public function onRefund(PaymentEvent $event) {
    $payment = $event->getPayment();
    $this->logStorage->generate($payment, 'payment_refunded', [
      'payment_remote_id' => $payment->getRemoteId(),
    ])->save();
  }

}
