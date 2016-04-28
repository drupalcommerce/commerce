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
      ],
      'order_information' => [
        'label' => $this->t('Order information'),
      ],
      'review' => [
        'label' => $this->t('Review'),
        'next_label' => $this->t('Continue to review'),
      ],
    ] + parent::getSteps();
  }

}
