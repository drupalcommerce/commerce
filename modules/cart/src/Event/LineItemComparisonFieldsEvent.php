<?php

/**
 * @file
 * Contains \Drupal\commerce_cart\Event\LineItemComparisonFieldsEvent.
 */

namespace Drupal\commerce_cart\Event;

use Drupal\commerce_order\Entity\LineItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the line item comparison fields event.
 *
 * @see \Drupal\commerce_cart\Event\CartEvents
 */
class LineItemComparisonFieldsEvent extends Event {

  /**
   * The comparison fields.
   *
   * @var string[]
   */
  protected $comparisonFields;

  /**
   * The line item being added to the cart.
   *
   * @var \Drupal\commerce_order\Entity\LineItemInterface
   */
  protected $lineItem;

  /**
   * Constructs a new LineItemComparisonFieldsEvent.
   *
   * @param string[] $comparison_fields
   *   The comparison fields.
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The line item being added to the cart.
   */
  public function __construct(array $comparison_fields, LineItemInterface $line_item) {
    $this->comparisonFields = $comparison_fields;
    $this->lineItem = $line_item;
  }

  /**
   * Gets the comparison fields.
   *
   * @return string[]
   *   The comparison fields.
   */
  public function getComparisonFields() {
    return $this->comparisonFields;
  }

  /**
   * Sets the comparison fields.
   *
   * @param string[] $comparison_fields
   *   The comparison fields.
   */
  public function setComparisonFields(array $comparison_fields) {
    $this->comparisonFields = $comparison_fields;
  }

  /**
   * The line item being added to the cart.
   *
   * @return \Drupal\commerce_order\Entity\LineItemInterface
   *   The line item being added to the cart.
   */
  public function getLineItem() {
    return $this->lineItem;
  }

}
