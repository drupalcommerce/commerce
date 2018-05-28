<?php

namespace Drupal\commerce_payment;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder for payments.
 */
class PaymentListBuilder extends EntityListBuilder {

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'payments';

  /**
   * Constructs a new PaymentListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, CurrencyFormatterInterface $currency_formatter, RouteMatchInterface $route_match) {
    parent::__construct($entity_type, $storage);

    $this->currencyFormatter = $currency_formatter;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('commerce_price.currency_formatter'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_payments';
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $order = $this->routeMatch->getParameter('commerce_order');
    return $this->storage->loadMultipleByOrder($order);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $entity */
    $payment_gateway_plugin = $entity->getPaymentGateway()->getPlugin();
    $operations = $payment_gateway_plugin->buildPaymentOperations($entity);
    // Filter out operations that aren't allowed.
    $operations = array_filter($operations, function ($operation) {
      return !empty($operation['access']);
    });
    // Build the url for each operation.
    $base_route_parameters = [
      'commerce_payment' => $entity->id(),
      'commerce_order' => $entity->getOrderId(),
    ];
    foreach ($operations as $operation_id => $operation) {
      $route_parameters = $base_route_parameters + ['operation' => $operation_id];
      $operation['url'] = new Url('entity.commerce_payment.operation_form', $route_parameters);
      $operations[$operation_id] = $operation;
    }
    // Add the non-gateway-specific operations.
    if ($entity->access('delete')) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => $entity->toUrl('delete-form'),
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Payment');
    $header['state'] = $this->t('State');
    $header['payment_gateway'] = $this->t('Payment gateway');
    $header['remote_id'] = $this->t('Remote ID');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $entity */
    $amount = $entity->getAmount();
    $formatted_amount = $this->currencyFormatter->format($amount->getNumber(), $amount->getCurrencyCode());
    $refunded_amount = $entity->getRefundedAmount();
    if ($refunded_amount && !$refunded_amount->isZero()) {
      $formatted_amount .= ' ' . $this->t('Refunded:') . ' ';
      $formatted_amount .= $this->currencyFormatter->format($refunded_amount->getNumber(), $refunded_amount->getCurrencyCode());
    }
    $payment_gateway = $entity->getPaymentGateway();

    $row['label'] = $formatted_amount;
    $row['state'] = $entity->getState()->getLabel();
    $row['payment_gateway'] = $payment_gateway ? $payment_gateway->label() : '';
    $row['remote_id'] = $entity->getRemoteId() ?: $this->t('N/A');

    return $row + parent::buildRow($entity);
  }

}
