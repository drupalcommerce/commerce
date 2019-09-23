<?php

namespace Drupal\commerce_tax\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the tax number type plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\TaxNumberType.
 *
 * @Annotation
 */
class CommerceTaxNumberType extends Plugin {

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

  /**
   * The supported countries.
   *
   * An array of country codes.
   *
   * @var string[]
   */
  public $countries = [];

  /**
   * Example tax numbers.
   *
   * When available, shown to users in validation errors.
   *
   * @var string[]
   */
  public $examples = [];

}
