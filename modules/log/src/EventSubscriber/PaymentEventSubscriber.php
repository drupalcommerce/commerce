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
      PaymentEvents::PAYMENT_PRESAVE => ['onPaymentPresave', -100],
      PaymentEvents::PAYMENT_PREDELETE => ['onPaymentPredelete', -100],
    ];
    return $events;
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
    $this->logPayment($event->getPayment());
  }

  /**
   * Creates a log when a payment is deleted.
   *
   * @param \Drupal\commerce_payment\Event\PaymentEvent $event
   *   The payment event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onPaymentPredelete(PaymentEvent $event) {
    $event->getPayment()->setState('deleted');
    $this->logPayment($event->getPayment());
  }

  /**
   * Creates a log when a payment is changed.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function logPayment(PaymentInterface $payment) {
    $refund = $payment->getRefundedAmount();
    $isNew = $payment->isNew();
    $previousAmount =  FALSE;
    if (!empty($payment->original) && !$payment->getAmount()->equals($payment->original->getAmount())) {
      $previousAmount = $payment->original->getAmount();
    }
    if ($refund && Calculator::trim($refund->getNumber())) {
      if (!empty($payment->original) && !$payment->getRefundedAmount()->equals($payment->original->getRefundedAmount())) {
        $refund = $payment->getRefundedAmount()->subtract($payment->original->getRefundedAmount());
      }
      $this->logStorage->generate($payment->getOrder(), 'payment_refunded', [
        'id' => $payment->id(),
        'remote_id' => $payment->getRemoteId(),
        'refunded_amount' => $refund,
        'state' => $payment->getState()->value,
        'new' => $isNew,
      ])->save();
    }
    else {
      $this->logStorage->generate($payment->getOrder(), 'payment_log', [
        'id' => $payment->id(),
        'remote_id' => $payment->getRemoteId(),
        'new' => $isNew,
        'amount' => $payment->getAmount(),
        'previous_amount' => $previousAmount,
        'state' => $payment->getState()->value,
      ])->save();
    }
  }

}
