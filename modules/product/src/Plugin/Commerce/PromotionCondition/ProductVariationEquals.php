<?php

namespace Drupal\commerce_product\Plugin\Commerce\PromotionCondition;

use Drupal\commerce_promotion\Plugin\Commerce\PromotionCondition\PromotionConditionBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Order: Total product variation equals' condition.
 *
 * @CommercePromotionCondition(
 *   id = "commerce_product_variation_equals",
 *   label = @Translation("Product variation equals"),
 *   target_entity_type = "commerce_order_item",
 * )
 */
class ProductVariationEquals extends PromotionConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The product variation storage.
   *
   * @var \Drupal\commerce_product\ProductVariationStorageInterface
   */
  protected $productVariationStorage;

  /**
   * Constructs a new ProductEquals object.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->productVariationStorage = $entity_type_manager->getStorage('commerce_product_variation');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
             'variation_id' => NULL,
           ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['variation_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => t('Product variation'),
      '#default_value' => $this->productVariationStorage->load($this->configuration['variation_id']),
      '#target_type' => 'commerce_product_variation',
      '#selection_handler' => 'default',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $this->configuration['variation_id'] = $values['variation_id'];
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $variant_id = $this->configuration['variation_id'];
    if (empty($variant_id)) {
      return FALSE;
    }

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $current_product_variation */
    $current_product_variation = $this->getTargetEntity()->getPurchasedEntity();

    $result = ($current_product_variation->id() == $variant_id);
    return $this->isNegated() ? !$result : $result;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Compares the purchased product variation.');
  }

}
