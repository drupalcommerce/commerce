<?php
/**
 * @file
 * Contains \Drupal\commerce_tax\Annotation\TaxRateResolver.
 */

namespace Drupal\commerce_tax\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a TaxRate annotation object.
 *
 * Plugin Namespace: Plugin\commerce_tax\TaxRateResolver
 *
 * @see \Drupal\commerce_tax\TaxRateResolverManager
 * @see plugin_api
 *
 * @Annotation
 */
class TaxRateResolver extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;
}
