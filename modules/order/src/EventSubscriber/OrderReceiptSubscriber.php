<?php

namespace Drupal\commerce_order\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\Renderer;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sends a receipt email when an order is placed.
 */
class OrderReceiptSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The order type entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderTypeStorage;

  /**
   * The entity view builder for profiles.
   *
   * @var \Drupal\profile\ProfileViewBuilder
   */
  protected $profileViewBuilder;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new OrderReceiptSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MailManagerInterface $mail_manager, Renderer $renderer) {
    $this->orderTypeStorage = $entity_type_manager->getStorage('commerce_order_type');
    $this->profileViewBuilder = $entity_type_manager->getViewBuilder('profile');
    $this->mailManager = $mail_manager;
    $this->renderer = $renderer;
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
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $this->orderTypeStorage->load($order->bundle());
    if (!$order_type->shouldSendReceipt()) {
      return;
    }
    $customer = $order->getCustomer();
    if (!$customer) {
      // Anonymous orders have no customer attached, therefore nobody to email.
      return;
    }

    $params = [
      'headers' => [
        'Content-Type' => 'text/html',
      ],
      'from' => $order->getStore()->getEmail(),
      'subject' => $this->t('Order #@number confirmed', ['@number' => $order->getOrderNumber()]),
    ];
    if ($receipt_bcc = $order_type->getReceiptBcc()) {
      $params['headers']['Bcc'] = $receipt_bcc;
    }

    $build = [
      '#theme' => 'commerce_order_receipt',
      '#order_entity' => $order,
    ];
    if ($billing_profile = $order->getBillingProfile()) {
      $build['#billing_information'] = $this->profileViewBuilder->view($billing_profile);
    }
    $params['body'] = $this->renderer->executeInRenderContext(new RenderContext(), function () use ($build) {
      return $this->renderer->render($build);
    });

    $this->mailManager->mail('commerce_order', 'receipt', $customer->getEmail(), $customer->getPreferredLangcode(), $params);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = ['commerce_order.place.post_transition' => ['sendOrderReceipt', -100]];
    return $events;
  }

}
