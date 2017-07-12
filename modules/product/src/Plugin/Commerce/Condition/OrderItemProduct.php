<?php

namespace Drupal\commerce_product\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the product condition for order items.
 *
 * @CommerceCondition(
 *   id = "order_item_product",
 *   label = @Translation("Product"),
 *   display_label = @Translation("Limit by product"),
 *   category = @Translation("Product"),
 *   entity_type = "commerce_order_item",
 * )
 */
class OrderItemProduct extends ConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The product storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productStorage;

  /**
   * Constructs a new OrderItemProduct object.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name.
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
      'products' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $products = NULL;
    $product_ids = array_column($this->configuration['products'], 'product_id');
    if (!empty($product_ids)) {
      $products = $this->productStorage->loadMultiple($product_ids);
    }
    $form['products'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Products'),
      '#default_value' => $products,
      '#target_type' => 'commerce_product',
      '#tags' => TRUE,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['products'] = [];
    foreach ($values['products'] as $value) {
      $this->configuration['products'][] = [
        'product_id' => $value['target_id'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity;
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchasable_entity */
    $purchasable_entity = $order_item->getPurchasedEntity();
    if (!$purchasable_entity || $purchasable_entity->getEntityTypeId() != 'commerce_product_variation') {
      return FALSE;
    }
    $product_ids = array_column($this->configuration['products'], 'product_id');

    return in_array($purchasable_entity->getProductId(), $product_ids);
  }

}
