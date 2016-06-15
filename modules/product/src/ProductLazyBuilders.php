<?php

namespace Drupal\commerce_product;

use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides #lazy_builder callbacks.
 */
class ProductLazyBuilders {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The line item type map.
   *
   * @var \Drupal\commerce_product\LineItemTypeMapInterface
   */
  protected $lineItemTypeMap;

  /**
   * Constructs a new CartLazyBuilders object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\commerce_product\LineItemTypeMapInterface $line_item_type_map
   *   The line item type map.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, LineItemTypeMapInterface $line_item_type_map) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->lineItemTypeMap = $line_item_type_map;
  }

  /**
   * Builds the add to cart form.
   *
   * @param string $product_id
   *   The product ID.
   * @param string $view_mode
   *   The view mode used to render the product.
   * @param bool $combine
   *   TRUE to combine line items containing the same product variation.
   *
   * @return array
   *   A renderable array containing the cart form.
   */
  public function addToCartForm($product_id, $view_mode, $combine) {
    $product = $this->entityTypeManager->getStorage('commerce_product')->load($product_id);
    $line_item_type_id = $this->lineItemTypeMap->getLineItemTypeId($product->bundle());
    $line_item = $this->entityTypeManager->getStorage('commerce_line_item')->create([
      'type' => $line_item_type_id,
    ]);
    $form_state_additions = [
      'product' => $product,
      'view_mode' => $view_mode,
      'settings' => [
        'combine' => $combine,
      ],
    ];
    return $this->entityFormBuilder->getForm($line_item, 'add_to_cart', $form_state_additions);
  }

}
