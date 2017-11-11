<?php

namespace Drupal\commerce_log\Plugin\LogCategory;

/**
 * Defines the interface for log categories.
 */
interface LogCategoryInterface {

  /**
   * Gets the category ID.
   *
   * @return string
   *   The category ID.
   */
  public function getId();

  /**
   * Gets the translated label.
   *
   * @return string
   *   The translated label.
   */
  public function getLabel();

  /**
   * Gets the entity type ID.
   *
   * For example, "node" if all log templates in the category are used on content.
   *
   * @return string
   *   The entity type id.
   */
  public function getEntityTypeId();

}
