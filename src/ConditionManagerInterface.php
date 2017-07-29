<?php

namespace Drupal\commerce;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;

/**
 * Defines the interface for commerce_condition plugin managers.
 */
interface ConditionManagerInterface extends CategorizingPluginManagerInterface {

  /**
   * Gets the filtered plugin definitions.
   *
   * @param string $parent_entity_type_id
   *   The parent entity type ID. For example: 'commerce_promotion' if the
   *   conditions are being loaded for a promotion.
   * @param array $entity_type_ids
   *   The entity type IDs. For example: ['commerce_order'] to get
   *   only conditions that evaluate orders.
   *
   * @return array
   *   The filtered plugin definitions.
   */
  public function getFilteredDefinitions($parent_entity_type_id, array $entity_type_ids);

}
