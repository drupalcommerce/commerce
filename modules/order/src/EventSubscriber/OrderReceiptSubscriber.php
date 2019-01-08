<?php

namespace Drupal\commerce_order\EventSubscriber;

use Drupal\commerce_order\OrderTotalSummaryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\Renderer;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
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
   * The order total summary.
   *
   * @var \Drupal\commerce_order\OrderTotalSummaryInterface
   */
  protected $orderTotalSummary;

  /**
   * The entity view builder for profiles.
   *
   * @var \Drupal\profile\ProfileViewBuilder
   */
  protected $profileViewBuilder;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

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
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The theme initialization.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * Constructs a new OrderReceiptSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\commerce_order\OrderTotalSummaryInterface $order_total_summary
   *   The order total summary.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $theme_initialization
   *   The theme initialization.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, MailManagerInterface $mail_manager, OrderTotalSummaryInterface $order_total_summary, Renderer $renderer, ThemeManagerInterface $theme_manager, ThemeInitializationInterface $theme_initialization) {
    $this->orderTypeStorage = $entity_type_manager->getStorage('commerce_order_type');
    $this->orderTotalSummary = $order_total_summary;
    $this->profileViewBuilder = $entity_type_manager->getViewBuilder('profile');
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
    $this->renderer = $renderer;
    $this->themeManager = $theme_manager;
    $this->themeInitialization = $theme_initialization;
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
    $to = $order->getEmail();
    if (!$to) {
      // The email should not be empty, unless the order is malformed.
      return;
    }

    $params = [
      'headers' => [
        'Content-Type' => 'text/html; charset=UTF-8;',
        'Content-Transfer-Encoding' => '8Bit',
      ],
      'from' => $order->getStore()->getEmail(),
      'subject' => $this->t('Order #@number confirmed', ['@number' => $order->getOrderNumber()]),
      'order' => $order,
    ];
    if ($receipt_bcc = $order_type->getReceiptBcc()) {
      $params['headers']['Bcc'] = $receipt_bcc;
    }

    // Switch the theme to the configured mail theme.
    $mail_theme = NULL;
    $current_active_theme = $this->themeManager->getActiveTheme();

    // The Mail System module swaps out core's MailManager, adding a
    // getMailTheme() method. However, this method is not on any interface.
    if (method_exists($this->mailManager, 'getMailTheme')) {
      $mail_theme = $this->mailManager->getMailTheme();
      if ($mail_theme != $current_active_theme->getName()) {
        $initialized_mail_theme = $this->themeInitialization->initTheme($mail_theme);
        $this->themeManager->setActiveTheme($initialized_mail_theme);
      }
    }

    try {
      $build = [
        '#theme' => 'commerce_order_receipt',
        '#order_entity' => $order,
        '#totals' => $this->orderTotalSummary->buildTotals($order),
      ];
      if ($billing_profile = $order->getBillingProfile()) {
        $build['#billing_information'] = $this->profileViewBuilder->view($billing_profile);
      }
      $params['body'] = $this->renderer->executeInRenderContext(new RenderContext(), function () use ($build) {
        return $this->renderer->render($build);
      });
    }
    finally {
      // Revert the active theme.
      if ($mail_theme != $current_active_theme->getName()) {
        $this->themeManager->setActiveTheme($current_active_theme);
      }
    }

    // Replicated logic from EmailAction and contact's MailHandler.
    $customer = $order->getCustomer();
    if ($customer->isAuthenticated()) {
      $langcode = $customer->getPreferredLangcode();
    }
    else {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
    }

    $this->mailManager->mail('commerce_order', 'receipt', $to, $langcode, $params);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = ['commerce_order.place.post_transition' => ['sendOrderReceipt', -100]];
    return $events;
  }

}
