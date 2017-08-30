<?php

namespace Drupal\commerce\Plugin\Commerce\Condition;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for conditions.
 */
interface ConditionInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Gets the condition label.
   *
   * @return string
   *   The condition label.
   */
  public function getLabel();

  /**
   * Gets the condition display label.
   *
   * Shown in the condition UI when enabling/disabling a condition.
   *
   * @return string
   *   The condition display label.
   */
  public function getDisplayLabel();

  /**
   * Gets the condition entity type ID.
   *
   * This is the entity type ID of the entity passed to evaluate().
   *
   * @return string
   *   The condition's entity type ID.
   */
  public function getEntityTypeId();

  /**
   * Evaluates the condition.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the condition has been met, FALSE otherwise.
   */
  public function evaluate(EntityInterface $entity);

}
