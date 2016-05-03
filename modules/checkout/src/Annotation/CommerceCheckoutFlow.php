<?php

namespace Drupal\commerce_checkout\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the checkout flow plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\CheckoutFlow.
 *
 * @Annotation
 */
class CommerceCheckoutFlow extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
