<?php

namespace Drupal\commerce_number_pattern\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the number pattern plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\NumberPattern.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class CommerceNumberPattern extends Plugin {

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
