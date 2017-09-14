<?php

namespace Drupal\commerce_log\EventSubscriber;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Event\PaymentEvent;
use Drupal\commerce_payment\Event\PaymentEvents;
use Drupal\commerce_price\Calculator;
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
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->logStorage = $entity_type_manager->getStorage('commerce_log');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      PaymentEvents::PAYMENT_INSERT => ['onPaymentInsert', -100],
      PaymentEvents::PAYMENT_PRESAVE => ['onPaymentPresave', -100],
      PaymentEvents::PAYMENT_DELETE => ['onPaymentDelete', -100],
    ];
    return $events;
  }

  /**
   * Creates a log when a payment is inserted.
   *
   * @param \Drupal\commerce_payment\Event\PaymentEvent $event
   *   The payment event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onPaymentInsert(PaymentEvent $event) {
    $this->logPayment($event->getPayment(), 'payment_inserted');
  }

  /**
   * Creates a log before a payment is saved.
   *
   * @param \Drupal\commerce_payment\Event\PaymentEvent $event
   *   The payment event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onPaymentPresave(PaymentEvent $event) {
    $payment = $event->getPayment();
    $refund = $payment->getRefundedAmount();
    if (!$payment->isNew() && !$payment->getAmount()->equals($payment->original->getAmount())) {
      $this->logStorage->generate($payment, 'payment_price_updated', [
        'previous_amount' => $payment->original->getAmount(),
        'current_amount' => $payment->getAmount(),
      ])->save();
    }
    if ($refund && Calculator::trim($refund->getNumber())) {
      $this->logStorage->generate($payment, 'payment_refunded', [
        'refunded_amount' => $refund,
      ])->save();
    }
  }

  /**
   * Creates a log when a payment is deleted.
   *
   * @param \Drupal\commerce_payment\Event\PaymentEvent $event
   *   The payment event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onPaymentDelete(PaymentEvent $event) {
    $this->logPayment($event->getPayment(), 'payment_deleted');
  }

  /**
   * Creates a log when a payment is changed.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param string $templateId
   *   The log template ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function logPayment(PaymentInterface $payment, $templateId) {
    $this->logStorage->generate($payment, $templateId, [
      'amount' => $payment->getAmount(),
    ])->save();
  }

}
