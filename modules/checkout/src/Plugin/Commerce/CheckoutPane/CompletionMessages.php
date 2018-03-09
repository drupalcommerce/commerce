<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Acts as a container to collect all completion messages.
 *
 * @package Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane
 */
class CompletionMessages implements \Iterator, \Countable {

  /**
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup[]
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
   * Adds a message to the array.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   The message to add.
   */
  public function addMessage(TranslatableMarkup $message) {
    $this->messages[] = $message;
  }

  /**
   * Gets the current message.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The current message.
   */
  public function current() {
    return $this->messages[$this->position];
  }

  /**
   * {@inheritdoc}
   */
  public function next() {
    ++$this->position;
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return $this->position;
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    return isset($this->messages[$this->position]);
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    $this->position = 0;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->messages);
  }

}
