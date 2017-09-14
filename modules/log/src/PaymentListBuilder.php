<?php

namespace Drupal\commerce_log;

use Drupal\commerce_payment\PaymentListBuilder as BasePaymentListBuilder;

/**
 * Overrides the list builder for payments.
 */
class PaymentListBuilder extends BasePaymentListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $entityIds = [];
    foreach ($this->load() as $entity) {
      $entityIds[] = $entity->id();
    }
    $build['log']['title'] = [
      '#markup' => '<h3>' . $this->t('Payment activity') . '</h3>',
    ];
    $build['log']['activity'] = [
      '#type' => 'view',
      '#name' => 'commerce_activity',
      '#display_id' => 'default',
      '#arguments' => [implode('+', $entityIds), 'commerce_payment'],
      '#embed' => FALSE,
    ];
    return $build;
  }

}
