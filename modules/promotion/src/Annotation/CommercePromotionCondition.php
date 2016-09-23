<?php

namespace Drupal\commerce_promotion\Annotation;

use Drupal\Core\Condition\Annotation\Condition;

/**
 * Defines the promotion condition plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\PromotionCondition.
 *
 * @Annotation
 */
class CommercePromotionCondition extends Condition {

  /**
   * The target entity type this action applies to.
   *
   * For example, this should be 'commerce_order' or 'commerce_order_item'.
   *
   * @var string
   */
  public $target_entity_type;

}
