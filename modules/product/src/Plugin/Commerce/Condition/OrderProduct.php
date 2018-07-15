<?php

namespace Drupal\commerce_product\Plugin\Commerce\Condition;

use Drupal\commerce\EntityUuidMapperInterface;
use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the product condition for orders.
 *
 * @CommerceCondition(
 *   id = "order_product",
 *   label = @Translation("Product"),
 *   display_label = @Translation("Order contains specific products"),
 *   category = @Translation("Products"),
 *   entity_type = "commerce_order",
 *   weight = -1,
 * )
 */
class OrderProduct extends ConditionBase implements ContainerFactoryPluginInterface {

  use ProductTrait;

  /**
   * Constructs a new OrderProduct object.
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
   * @param \Drupal\commerce\EntityUuidMapperInterface $entity_uuid_mapper
   *   The entity UUID mapper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityUuidMapperInterface $entity_uuid_mapper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->productStorage = $entity_type_manager->getStorage('commerce_product');
    $this->entityUuidMapper = $entity_uuid_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('commerce.entity_uuid_mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    $product_ids = $this->getProductIds();
    foreach ($order->getItems() as $order_item) {
      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
      $purchased_entity = $order_item->getPurchasedEntity();
      if (!$purchased_entity || $purchased_entity->getEntityTypeId() != 'commerce_product_variation') {
        continue;
      }
      if (in_array($purchased_entity->getProductId(), $product_ids)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
