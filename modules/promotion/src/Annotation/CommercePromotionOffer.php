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
   * The offer entity type ID.
   *
   * This is the entity type ID of the entity passed to the plugin during execution.
   * For example: 'commerce_order'.
   *
   * @var string
   */
  public $entity_type;

}
