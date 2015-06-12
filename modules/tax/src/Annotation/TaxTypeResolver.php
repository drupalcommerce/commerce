<?php
/**
 * @file
 * Contains \Drupal\commerce_tax\Annotation\TaxTypeResolver.
 */

namespace Drupal\commerce_tax\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a TaxTypeResolver annotation object.
 *
 * Plugin Namespace: Plugin\CommerceTax\TaxTypeResolver
 *
 * @see \Drupal\commerce_tax\TaxTypeResolverManager
 * @see plugin_api
 *
 * @Annotation
 */
class TaxTypeResolver extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;
}
