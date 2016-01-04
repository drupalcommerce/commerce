<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Event\LineItemEvent.
 */

namespace Drupal\commerce_order\Event;

use Drupal\commerce_order\Entity\LineItemInterface;
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
   * @var \Drupal\commerce_order\Entity\LineItemInterface
   */
  protected $lineItem;

  /**
   * Constructs a new LineItemEvent.
   *
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The line item.
   */
  public function __construct(LineItemInterface $line_item) {
    $this->lineItem = $line_item;
  }

  /**
   * The line item the event refers to.
   *
   * @return \Drupal\commerce_order\Entity\LineItemInterface
   */
  public function getLineItem() {
    return $this->lineItem;
  }

}
