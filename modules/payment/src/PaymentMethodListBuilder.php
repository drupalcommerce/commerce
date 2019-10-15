<?php

namespace Drupal\commerce_payment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines the list builder for payment methods.
 */
class PaymentMethodListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'payment_methods';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_payment_methods';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    /** @var \Drupal\Core\Routing\RouteMatchInterface $route */
    $route = \Drupal::service('current_route_match');
    $user = $route->getParameter('user');

    $query = $this->getStorage()->getQuery()
      ->condition('uid', $user->id())
      ->condition('reusable', TRUE)
      ->sort('method_id');
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Payment method');
    $header['expires'] = $this->t('Expires');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $entity */
    $expires = $entity->getExpiresTime();

    $row['label']['data'] = [
      '#markup' => $entity->label(),
    ];
    if ($entity->bundle() == 'credit_card') {
      $icon = 'payment-method-icon--' . $entity->card_type->value;
      $row['label']['data']['#prefix'] = '<span class="payment-method-icon ' . $icon . '"></span>';
    }
    $row['expires']['data'] = [
      '#markup' => $expires ? date('n/Y', $expires) : $this->t('Never'),
    ];
    if ($entity->isExpired()) {
      $row['expires']['data']['#suffix'] = '<br><strong>' . $this->t('Expired') . '</strong>';
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['#attached']['library'][] = 'commerce_payment/payment_method_icons';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    $build = parent::buildOperations($entity);
    // Replace the dropbuttons with normal links.
    unset($build['#type']);
    $build['#theme'] = 'links';

    return $build;
  }

}
