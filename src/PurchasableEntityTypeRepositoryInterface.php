<?php

namespace Drupal\commerce;

/**
 * Provides an interface for methods to help loading purchasable entity types.
 */
interface PurchasableEntityTypeRepositoryInterface {

  /**
   * Gets the full list of purchasable entity types.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An array of purchasable entity type definitions keyed by entity type ID.
   */
  public function getPurchasableEntityTypes();

  /**
   * Builds a list of entity type labels suitable for a Form API options list.
   *
   * @return array
   *   An array of purchasable entity type labels keyed by entity type ID.
   */
  public function getPurchasableEntityTypeLabels();

  /**
   * Returns a sensible default purchasable entity type.
   *
   * This is primarily needed to set an entity type to target in the base
   * field definition for the purchasable entity field on order items. The core
   * EntityReferenceItem field definition defaults the base field settings
   * array to specify a target_type of node or user, and it is never overridden
   * by bundle specific settings before the Views module uses that target_type
   * to populate the list of view modes when rendering the purchasable entity
   * reference field value as a "Rendered entity" in Views.
   *
   * As such, our workaround is to determine a "default" purchasable entity
   * type, privileging commerce_product_variation as the dominant use case if
   * it exists and just selecting the first available entity type if not. Sites
   * that need to set a specific default target_type can still do so by
   * decorating the default service and overriding this method to return your
   * purchasable entity type.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The default purchasable entity type definition.
   */
  public function getDefaultPurchasableEntityType();

}
