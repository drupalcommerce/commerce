<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Event\LineItemEvent.
 */

namespace Drupal\commerce_order\Event;

use Drupal\commerce_order\LineItemInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the line item event.
 *
 * @see \Drupal\commerce_order\Event\OrderEvents
 */
class LineItemEvent extends Event {

  /**
   * The line item.
   *
   * @var \Drupal\commerce_order\LineItemInterface
   */
  protected $lineItem;

  /**
   * Constructs a new LineItemEvent.
   *
   * @param \Drupal\commerce_order\LineItemInterface $lineItem
   *   The line item.
   */
  public function __construct(LineItemInterface $lineItem) {
    $this->lineItem = $lineItem;
  }

  /**
   * The line item the event refers to.
   *
   * @return \Drupal\commerce_order\LineItemInterface
   */
  public function getLineItem() {
    return $this->lineItem;
  }

}
