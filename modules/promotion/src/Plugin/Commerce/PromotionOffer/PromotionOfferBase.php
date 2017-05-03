<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\EntityAdjustableInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Core\Executable\ExecutablePluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base class for offers.
 */
abstract class PromotionOfferBase extends ExecutablePluginBase implements PromotionOfferInterface {

  use ContextAwarePluginAssignmentTrait;

  /**
   * The rounder.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * Constructs a new PromotionOfferBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The pluginId for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The rounder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RounderInterface $rounder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    $this->rounder = $rounder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_price.rounder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPromotion() {
    return $this->getContextValue('commerce_promotion');
  }

  /**
   * {@inheritdoc}
   */
  public function getOrder() {
    return $this->getContextValue('commerce_order');
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

  /**
   * Applies the promotion offer's adjustment to an adjustable entity.
   *
   * @param \Drupal\commerce_order\EntityAdjustableInterface $entity
   *   The adjustable entity.
   * @param \Drupal\commerce_price\Price $amount
   *   The price object.
   */
  protected function applyAdjustment(EntityAdjustableInterface $entity, Price $amount) {
    $entity->addAdjustment(new Adjustment([
      'type' => 'promotion',
      // @todo Change to label from UI when added in #2770731.
      'label' => t('Discount'),
      'amount' => $amount->multiply('-1'),
      'source_id' => $this->getPromotion()->id(),
    ]));
  }

}
