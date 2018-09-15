<?php

namespace Drupal\commerce_cart_big_pipe\Form;

use Drupal\commerce_cart\Form\AddToCartForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a slow to build add to cart form, to test streaming.
 */
class BigPipeAddToCartForm extends AddToCartForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Sleep between 0.0 and 2.0 seconds.
    $load_slowdown = mt_rand(0, 20) / 10;
    $this->messenger()->addMessage(sprintf('Delayed form build by %s seconds', $load_slowdown));
    sleep($load_slowdown);
    return parent::buildForm($form, $form_state);
  }

}
