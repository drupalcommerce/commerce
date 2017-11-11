<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Event\FilterPaymentGatewaysEvent;
use Drupal\commerce_payment\Event\PaymentEvents;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the payment gateway storage.
 */
class PaymentGatewayStorage extends ConfigEntityStorage implements PaymentGatewayStorageInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a PaymentGatewayStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager);
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadForUser(UserInterface $account) {
    $payment_gateways = $this->loadByProperties(['status' => TRUE]);
    $payment_gateways = array_filter($payment_gateways, function ($payment_gateway) {
      return $payment_gateway->getPlugin() instanceof SupportsStoredPaymentMethodsInterface;
    });
    // @todo Implement resolving logic.
    $payment_gateway = reset($payment_gateways);

    return $payment_gateway;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleForOrder(OrderInterface $order) {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $payment_gateways */
    $payment_gateways = $this->loadByProperties(['status' => TRUE]);
    // Allow the list of payment gateways to be filtered via code.
    $event = new FilterPaymentGatewaysEvent($payment_gateways, $order);
    $this->eventDispatcher->dispatch(PaymentEvents::FILTER_PAYMENT_GATEWAYS, $event);
    $payment_gateways = $event->getPaymentGateways();
    // Evaluate conditions for the remaining ones.
    foreach ($payment_gateways as $payment_gateway_id => $payment_gateway) {
      if (!$payment_gateway->applies($order)) {
        unset($payment_gateways[$payment_gateway_id]);
      }
    }
    uasort($payment_gateways, [$this->entityType->getClass(), 'sort']);

    return $payment_gateways;
  }

}
