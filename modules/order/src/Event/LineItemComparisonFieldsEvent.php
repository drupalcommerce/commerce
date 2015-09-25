<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Event\LineItemComparisonFieldsEvent.
 */

namespace Drupal\commerce_order\Event;

use Drupal\commerce\PurchasableEntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the available countries event.
 *
 * @see \Drupal\address\Event\OrderEvents
 */
class LineItemComparisonFieldsEvent extends Event {

  /**
   * The comparison fields.
   *
   * A list of fields.
   *
   * @var array
   */
  protected $comparisonFields;

  /**
   * The purchasable entity.
   *
   * @var \Drupal\commerce\PurchasableEntityInterface
   */
  protected $entity;

  /**
   * Constructs a new LineItemComparisonFieldsEvent object.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The Purchasable Entity
   *
   * @param array $comparisonFields
   *   An initial list of comparison fields.
   */
  public function __construct(PurchasableEntityInterface $entity, array $comparisonFields) {
    $this->entity = $entity;
    $this->comparisonFields = $comparisonFields;
  }

  /**
   * Gets the comparison fields.
   *
   * @return array
   *   The comparison fields.
   */
  public function getComparisonFields() {
    return $this->comparisonFields;
  }

}

