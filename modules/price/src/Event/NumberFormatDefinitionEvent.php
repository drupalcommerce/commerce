<?php

namespace Drupal\commerce_price\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the number format definition event.
 *
 * @see \Drupal\commerce_price\Event\PriceEvents
 */
class NumberFormatDefinitionEvent extends Event {

  /**
   * The number format definition.
   *
   * @var array
   */
  protected $definition;

  /**
   * Constructs a new NumberFormatDefinitionEvent.
   *
   * @param array $definition
   *   The number format definition.
   */
  public function __construct(array $definition) {
    $this->definition = $definition;
  }

  /**
   * Gets the number format definition.
   *
   * @return array
   *   The number format definition.
   */
  public function getDefinition() {
    return $this->definition;
  }

  /**
   * Sets the number format definition.
   *
   * @param array $definition
   *   The number format definition.
   *
   * @return $this
   */
  public function setDefinition(array $definition) {
    $this->definition = $definition;
    return $this;
  }

}
