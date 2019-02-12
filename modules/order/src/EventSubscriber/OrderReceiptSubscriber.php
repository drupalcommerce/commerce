<?php

namespace Drupal\commerce_order\EventSubscriber;

use Drupal\commerce\MailHandlerInterface;
use Drupal\commerce_order\OrderTotalSummaryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sends a receipt email when an order is placed.
 */
class OrderReceiptSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mail handler.
   *
   * @var \Drupal\commerce\MailHandlerInterface
   */
  protected $mailHandler;

  /**
   * The order total summary.
   *
   * @var \Drupal\commerce_order\OrderTotalSummaryInterface
   */
  protected $orderTotalSummary;

  /**
   * Constructs a new OrderReceiptSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce\MailHandlerInterface $mail_handler
   *   The mail handler.
   * @param \Drupal\commerce_order\OrderTotalSummaryInterface $order_total_summary
   *   The order total summary.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MailHandlerInterface $mail_handler, OrderTotalSummaryInterface $order_total_summary) {
    $this->entityTypeManager = $entity_type_manager;
    $this->mailHandler = $mail_handler;
    $this->orderTotalSummary = $order_total_summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = ['commerce_order.place.post_transition' => ['sendOrderReceipt', -100]];
    return $events;
  }

  /**
   * Sends an order receipt email.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function sendOrderReceipt(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $order_type_storage = $this->entityTypeManager->getStorage('commerce_order_type');
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $order_type_storage->load($order->bundle());
    if (!$order_type->shouldSendReceipt()) {
      return;
    }
    $to = $order->getEmail();
    if (!$to) {
      // The email should not be empty, unless the order is malformed.
      return;
    }

    $subject = $this->t('Order #@number confirmed', ['@number' => $order->getOrderNumber()]);
    $body = [
      '#theme' => 'commerce_order_receipt',
      '#order_entity' => $order,
      '#totals' => $this->orderTotalSummary->buildTotals($order),
    ];
    if ($billing_profile = $order->getBillingProfile()) {
      $profile_view_builder = $this->entityTypeManager->getViewBuilder('profile');;
      $body['#billing_information'] = $profile_view_builder->view($billing_profile);
    }
    $params = [
      'id' => 'order_receipt',
      'from' => $order->getStore()->getEmail(),
      'bcc' => $order_type->getReceiptBcc(),
      'order' => $order,
    ];
    $customer = $order->getCustomer();
    if ($customer->isAuthenticated()) {
      $params['langcode'] = $customer->getPreferredLangcode();
    }
    $this->mailHandler->sendMail($to, $subject, $body, $params);
  }

}
