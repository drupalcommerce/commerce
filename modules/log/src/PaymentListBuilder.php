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
    $entities = $this->load();
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = reset($entities);
    if ($payment) {
      $build['log']['title'] = [
        '#markup' => '<h3>' . $this->t('Order activity') . '</h3>',
      ];
      $build['log']['activity'] = [
        '#type' => 'view',
        '#name' => 'commerce_activity',
        '#display_id' => 'default',
        '#arguments' => [$payment->getOrder()->id(), 'commerce_order'],
        '#embed' => FALSE,
      ];
    }
    return $build;
  }

}
