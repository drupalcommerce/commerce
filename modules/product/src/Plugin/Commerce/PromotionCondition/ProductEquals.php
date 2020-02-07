<?php

namespace Drupal\commerce_product\Plugin\Commerce\PromotionCondition;

use Drupal\commerce_promotion\Plugin\Commerce\PromotionCondition\PromotionConditionBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Order item: product equals' condition.
 *
 * @CommercePromotionCondition(
 *   id = "commerce_product_equals",
 *   label = @Translation("Product equals"),
 *   target_entity_type = "commerce_order_item",
 * )
 */
class ProductEquals extends PromotionConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The product storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productStorage;

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
    $this->productStorage = $entity_type_manager->getStorage('commerce_product');
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
             'product_id' => NULL,
           ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $product = $this->productStorage->load($this->configuration['product_id']);
    $form['product_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Product'),
      '#default_value' => $product,
      '#target_type' => 'commerce_product',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $this->configuration['product_id'] = $values['product_id'];
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $product_id = $this->configuration['product_id'];
    if (empty($product_id)) {
      return FALSE;
    }

    /** @var \Drupal\commerce_product\Entity\ProductInterface $current_product */
    $current_product = $this->getTargetEntity()->getPurchasedEntity()->getProduct();

    return $current_product->id() == $product_id;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Compares the purchased product.');
  }

}
