<?php

namespace Drupal\commerce\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the event for filtering the available conditions.
 *
 * @see \Drupal\commerce_payment\Event\PaymentEvents
 */
class FilterConditionsEvent extends Event {

  /**
   * The condition definitions.
   *
   * @var array
   */
  protected $definitions;

  /**
   * The parent entity type ID.
   *
   * @var string
   */
  protected $parentEntityTypeId;

  /**
   * Constructs a new FilterConditionsEvent object.
   *
   * @param array $definitions
   *   The condition definitions.
   * @param string $parent_entity_type_id
   *   The parent entity type ID.
   */
  public function __construct(array $definitions, $parent_entity_type_id) {
    $this->definitions = $definitions;
    $this->parentEntityTypeId = $parent_entity_type_id;
  }

  /**
   * Gets the condition definitions.
   *
   * @return array
   *   The condition definitions.
   */
  public function getDefinitions() {
    return $this->definitions;
  }

  /**
   * Sets the condition definitions.
   *
   * @param array $definitions
   *   The condition definitions.
   *
   * @return $this
   */
  public function setDefinitions(array $definitions) {
    $this->definitions = $definitions;
    return $this;
  }

  /**
   * Gets the parent entity type ID.
   *
   * @return string
   *   The parent entity type ID.
   */
  public function getParentEntityTypeId() {
    return $this->parentEntityTypeId;
  }

}
