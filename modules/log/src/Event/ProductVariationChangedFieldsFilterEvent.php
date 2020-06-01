<?php

namespace Drupal\commerce_log\Event;

use Symfony\Component\EventDispatcher\Event;

class ProductVariationChangedFieldsFilterEvent extends Event {

  /**
   * The removed order items.
   *
   * @var \Drupal\commerce_order\Entity\OrderItemInterface[]
   */
  protected $fields;

  /**
   * Constructs a new ProductVariationChangedFieldsFilterEvent.
   *
   * @param array $fields
   *   The fields.
   */
  public function __construct(array $fields) {
    $this->fields = $fields;
  }

  /**
   * Gets the fields.
   *
   * @return array
   *   The fields.
   */
  public function getFields() {
    return $this->fields;
  }

  /**
   * Sets the fields.
   *
   * @return \Drupal\commerce_log\Event\ProductVariationChangedFieldsFilterEvent
   *   The product variation changed field filter event.
   */
  public function setFields(array $fields) {
    $this->fields = $fields;
    return $this;
  }

}
