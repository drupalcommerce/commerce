<?php

namespace Drupal\commerce_log\Plugin\LogCategory;

/**
 * Defines the interface for log categories.
 */
interface LogCategoryInterface {

  /**
   * Gets the log category ID.
   *
   * @return string
   *   The log category ID.
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
   * Gets the entity type id.
   *
   * For example, "node" if all log category in the group are used on content.
   *
   * @return string
   *   The entity type id.
   */
  public function getEntityTypeId();

}
