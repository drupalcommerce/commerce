<?php

namespace Drupal\commerce_tax\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the tax type plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\TaxType.
 *
 * @Annotation
 */
class CommerceTaxType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
