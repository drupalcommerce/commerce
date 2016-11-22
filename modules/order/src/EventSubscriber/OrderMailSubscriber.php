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
 * Listens to order transition events and sends emails.
 */
class OrderMailSubscriber implements EventSubscriberInterface {
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
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The Drupal renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new OrderMailer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   A mail manager service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The Drupal renderer.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MailManagerInterface $mail_manager, Renderer $renderer) {
    $this->orderTypeStorage = $entity_type_manager->getStorage('commerce_order_type');
    $this->profileViewBuilder = $entity_type_manager->getViewBuilder('profile');
    $this->mailManager = $mail_manager;
    $this->renderer = $renderer;
  }

  /**
   * Sends order receipt to customer and store email.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function customerOrderReceipt(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    // Check if the emails are enabled for this order type.
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $this->orderTypeStorage->load($order->bundle());
    if (!$order_type->shouldSendReceipt()) {
      return;
    }

    $customer = $order->getCustomer();
    $store = $order->getStore();

    // Build the body of the email.
    $build = [
      '#theme' => 'commerce_order_receipt',
      '#order' => $order,
    ];

    $billing_profile = $order->getBillingProfile();
    if ($billing_profile) {
      $build['#billing_information'] = $this->profileViewBuilder->view($billing_profile);
    }

    $body = $this->renderer->executeInRenderContext(new RenderContext(), function () use ($build) {
      return $this->renderer->render($build);
    });

    // Send an email to the customer.
    $customer_subject = $this->t('Order #@number confirmed', ['@number' => $order->getOrderNumber()], ['langcode' => $customer->getPreferredLangcode()]);
    $params = [
      'headers' => [
        'Content-Type' => 'text/html',
      ],
      'from' => $order->getStore()->getEmail(),
      'subject' => $customer_subject,
      'body' => $body,
    ];

    if ($order_type->shouldAddReceiptBcc()) {
      $params['headers']['Bcc'] = $store->getEmail();
    }

    $this->mailManager->mail('commerce_order', 'receipt', $customer->getEmail(), $customer->getPreferredLangcode(), $params);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = ['commerce_order.place.post_transition' => ['customerOrderReceipt', -100]];
    return $events;
  }

}
