<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_order\EntityAdjustableInterface;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\Core\Executable\ExecutablePluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;

/**
 * Base class for Promotion Offer plugins.
 */
abstract class PromotionOfferBase extends ExecutablePluginBase implements PromotionOfferInterface {

  use ContextAwarePluginAssignmentTrait;

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
  public function applyAdjustment(EntityAdjustableInterface $entity, Price $amount) {
    $entity->addAdjustment(new Adjustment([
      'type' => 'promotion',
      // @todo Change to label from UI when added in #2770731.
      'label' => t('Discount'),
      'amount' => $amount->multiply('-1'),
      'source_id' => $this->getPluginId(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $contexts = $form_state->getTemporaryValue('gathered_contexts') ?: [];
    $form['context_mapping'] = $this->addContextAssignmentElement($this, $contexts);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

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
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

}
