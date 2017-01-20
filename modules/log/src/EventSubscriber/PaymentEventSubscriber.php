<?php

namespace Drupal\commerce_log\EventSubscriber;

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
      'commerce_payment.authorize.pre_transition' => ['onAuthorize', -100],
      'commerce_payment.void.pre_transition' => ['onVoid', -100],
      'commerce_payment.expire.pre_transition' => ['onExpire', -100],
      'commerce_payment.authorize_capture.pre_transition' => ['onAuthorizeCapture', -100],
      'commerce_payment.capture.pre_transition' => ['onCapture', -100],
      'commerce_payment.partially_refund.pre_transition' => ['onPartiallyRefund', -100],
      'commerce_payment.refund.pre_transition' => ['onRefund', -100],
    ];
    return $events;
  }

  /**
   * Creates a log when a payment is authorized.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onAuthorize(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $event->getEntity();
    $this->logStorage->generate($payment, 'payment_authorized', [
      'payment_remote_id' => $payment->getRemoteId(),
    ])->save();
  }

  /**
   * Creates a log when a payment is voided.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onVoid(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $event->getEntity();
    $this->logStorage->generate($payment, 'payment_voided', [
      'payment_remote_id' => $payment->getRemoteId(),
    ])->save();
  }

  /**
   * Creates a log when a payment is expired.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onExpire(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $event->getEntity();
    $this->logStorage->generate($payment, 'payment_expired', [
      'payment_remote_id' => $payment->getRemoteId(),
    ])->save();
  }

  /**
   * Creates a log when a payment is authorized and captured.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onAuthorizeCapture(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $event->getEntity();
    $this->logStorage->generate($payment, 'payment_authorized_captured', [
      'payment_remote_id' => $payment->getRemoteId(),
    ])->save();
  }

  /**
   * Creates a log when a payment is captured.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onCapture(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $event->getEntity();
    $this->logStorage->generate($payment, 'payment_captured', [
      'payment_remote_id' => $payment->getRemoteId(),
    ])->save();
  }

  /**
   * Creates a log when a payment is partially refunded.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onPartiallyRefund(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $event->getEntity();
    $this->logStorage->generate($payment, 'payment_partially_refunded', [
      'payment_remote_id' => $payment->getRemoteId(),
    ])->save();
  }

  /**
   * Creates a log when a payment is fully refunded.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onRefund(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $event->getEntity();
    $this->logStorage->generate($payment, 'payment_refunded', [
      'payment_remote_id' => $payment->getRemoteId(),
    ])->save();
  }

}
