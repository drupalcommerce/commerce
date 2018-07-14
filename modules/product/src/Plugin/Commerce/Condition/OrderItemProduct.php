<?php

namespace Drupal\commerce_product\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the product condition for order items.
 *
 * @CommerceCondition(
 *   id = "order_item_product",
 *   label = @Translation("Product"),
 *   display_label = @Translation("Specific products"),
 *   category = @Translation("Products"),
 *   entity_type = "commerce_order_item",
 *   weight = -1,
 * )
 */
class OrderItemProduct extends ConditionBase implements ContainerFactoryPluginInterface {

  use ProductTrait;

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
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity;
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
    $purchased_entity = $order_item->getPurchasedEntity();
    if (!$purchased_entity || $purchased_entity->getEntityTypeId() != 'commerce_product_variation') {
      return FALSE;
    }
    $product_ids = array_column($this->configuration['products'], 'product_id');

    return in_array($purchased_entity->getProductId(), $product_ids);
  }

}
