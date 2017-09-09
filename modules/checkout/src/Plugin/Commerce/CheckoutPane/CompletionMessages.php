<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\TranslatableInterface;

/**
 * Acts as a container to collect all completion messages.
 *
 * @package Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane
 */
class CompletionMessages implements \Iterator, \Countable {

  /**
   * @var \Drupal\Core\TypedData\TranslatableInterface[]
   */
  private $messages;

  /**
   * @var int
   */
  private $position;

  /**
   * Sets up the position to 0.
   */
  public function __construct() {
    $this->position = 0;
  }

  /**
   * Return the current element
   *
   * @link http://php.net/manual/en/iterator.current.php
   * @return mixed Can return any type.
   * @since 5.0.0
   */
  public function current() {
    return $this->messages[$this->position];
  }

  /**
   * Move forward to next element
   *
   * @link http://php.net/manual/en/iterator.next.php
   * @return void Any returned value is ignored.
   * @since 5.0.0
   */
  public function next() {
    ++$this->position;
  }

  /**
   * Return the key of the current element
   *
   * @link http://php.net/manual/en/iterator.key.php
   * @return mixed scalar on success, or null on failure.
   * @since 5.0.0
   */
  public function key() {
    return $this->position;
  }

  /**
   * Checks if current position is valid
   *
   * @link http://php.net/manual/en/iterator.valid.php
   * @return boolean The return value will be casted to boolean and then
   *   evaluated. Returns true on success or false on failure.
   * @since 5.0.0
   */
  public function valid() {
    return isset($this->messages[$this->position]);
  }

  /**
   * Rewind the Iterator to the first element
   *
   * @link http://php.net/manual/en/iterator.rewind.php
   * @return void Any returned value is ignored.
   * @since 5.0.0
   */
  public function rewind() {
    $this->position = 0;
  }

  /**
   * Adds a message to the array.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   */
  public function addMessage(TranslatableMarkup $message) {
    $this->messages[] = $message;
  }

  /**
   * Count elements of an object
   *
   * @link http://php.net/manual/en/countable.count.php
   * @return int The custom count as an integer.
   * </p>
   * <p>
   * The return value is cast to an integer.
   * @since 5.1.0
   */
  public function count() {
    return count($this->messages);
  }
}