<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow;

/**
 * Provides the default multistep checkout flow.
 *
 * @CommerceCheckoutFlow(
 *   id = "multistep_default",
 *   label = "Multistep - Default",
 * )
 */
class MultistepDefault extends CheckoutFlowWithPanesBase {

  /**
   * {@inheritdoc}
   */
  public function getSteps() {
    return [
      'login' => [
        'label' => $this->t('Login'),
        'previous_label' => $this->t('Return to login'),
        'has_order_summary' => FALSE,
      ],
      'order_information' => [
        'label' => $this->t('Order information'),
        'has_order_summary' => TRUE,
      ],
      'review' => [
        'label' => $this->t('Review'),
        'next_label' => $this->t('Continue to review'),
        'has_order_summary' => TRUE,
      ],
    ] + parent::getSteps();
  }

}
