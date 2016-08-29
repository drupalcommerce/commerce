<?php

namespace Drupal\commerce_payment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Defines the list builder for payments.
 */
class PaymentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'payments';

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
    $header['state'] = $this->t('State');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $entity */
    $row['label'] = $entity->label();
    $row['state'] = $entity->getState()->getLabel();

    return $row + parent::buildRow($entity);
  }

}
