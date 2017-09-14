<?php

namespace Drupal\commerce\Plugin\Commerce\Condition;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the base class for conditions.
 */
abstract class ConditionBase extends PluginBase implements ConditionInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration = [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel() {
    return $this->pluginDefinition['display_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->pluginDefinition['entity_type'];
  }

  /**
   * Asserts that the given entity is of the expected type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  protected function assertEntity(EntityInterface $entity) {
    $entity_type_id = $entity->getEntityTypeId();
    $condition_entity_type_id = $this->getEntityTypeId();
    if ($entity_type_id != $condition_entity_type_id) {
      throw new \InvalidArgumentException(sprintf('The condition requires a "%s" entity, but a "%s" entity was given.', $condition_entity_type_id, $entity_type_id));
    }
  }

  /**
   * Gets the comparison operators.
   *
   * @return array
   *   The comparison operators.
   */
  protected function getComparisonOperators() {
    return [
      '>' => $this->t('Greater than'),
      '>=' => $this->t('Greater than or equal to'),
      '<=' => $this->t('Less than or equal to'),
      '<' => $this->t('Less than'),
      '==' => $this->t('Equals'),
    ];
  }

}
