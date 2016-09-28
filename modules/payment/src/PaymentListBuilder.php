<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_price\NumberFormatterFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder for payments.
 */
class PaymentListBuilder extends EntityListBuilder {

  /**
   * The number formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface
   */
  protected $numberFormatter;

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
   * @param \Drupal\commerce_price\NumberFormatterFactoryInterface $number_formatter_factory
   *   The number formatter factory.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, NumberFormatterFactoryInterface $number_formatter_factory) {
    parent::__construct($entity_type, $storage);

    $this->numberFormatter = $number_formatter_factory->createInstance();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('commerce_price.number_formatter_factory')
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
  protected function getEntityIds() {
    /** @var \Drupal\Core\Routing\RouteMatchInterface $route */
    $route = \Drupal::service('current_route_match');
    $order = $route->getParameter('commerce_order');

    $query = $this->getStorage()->getQuery()
      ->condition('order_id', $order)
      ->sort('payment_id');
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
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
    $header['remote_id'] = $this->t('Remote ID');
    $header['state'] = $this->t('State');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $entity */
    $amount = $entity->getAmount();
    // @todo Refactor the number formatter to work with just a currency code.
    $currency = Currency::load($amount->getCurrencyCode());
    $formatted_amount = $this->numberFormatter->formatCurrency($amount->getNumber(), $currency);
    $refunded_amount = $entity->getRefundedAmount();
    if ($refunded_amount && !$refunded_amount->isZero()) {
      $formatted_amount .= ' Refunded: ' . $this->numberFormatter->formatCurrency($refunded_amount->getNumber(), $currency);
    }

    $row['label'] = $formatted_amount;
    $row['remote_id'] = $entity->getRemoteId();
    $row['state'] = $entity->getState()->getLabel();

    return $row + parent::buildRow($entity);
  }

}
