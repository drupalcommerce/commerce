<?php

namespace Drupal\commerce_promotion\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the promotion offer plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\PromotionOffer.
 *
 * @Annotation
 */
class CommercePromotionOffer extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * An array of context definitions describing the context used by the plugin.
   *
   * The array is keyed by context names.
   *
   * @var \Drupal\Core\Annotation\ContextDefinition[]
   */
  public $context = [];

  /**
   * The category under which the offer should listed in the UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

}
