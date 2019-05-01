<?php

namespace Drupal\commerce_payment\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the payment type plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\PaymentType.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class CommercePaymentType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The payment type label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The payment workflow.
   *
   * @var string
   */
  public $workflow = 'payment_default';

}
