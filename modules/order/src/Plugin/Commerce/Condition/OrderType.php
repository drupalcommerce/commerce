<?php

namespace Drupal\commerce_order\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\EntityBundleBase;

/**
 * Provides the type condition for orders.
 *
 * @CommerceCondition(
 *   id = "order_type",
 *   label = @Translation("Order type", context = "Commerce"),
 *   category = @Translation("Order", context = "Commerce"),
 *   entity_type = "commerce_order",
 * )
 */
class OrderType extends EntityBundleBase {}
