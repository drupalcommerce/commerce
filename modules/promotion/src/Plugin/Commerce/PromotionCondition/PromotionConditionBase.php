<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionCondition;

use Drupal\Core\Condition\ConditionPluginBase;

/**
 * Base class for Promotion Condition plugins.
 */
abstract class PromotionConditionBase extends ConditionPluginBase implements PromotionConditionInterface {

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityType() {
    return $this->pluginDefinition['target_entity_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntity() {
    return $this->getContextValue($this->getTargetEntityType());
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $result = $this->evaluate();
    return $this->isNegated() ? !$result : $result;
  }

}
