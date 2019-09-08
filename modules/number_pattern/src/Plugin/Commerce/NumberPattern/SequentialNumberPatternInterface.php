<?php

namespace Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the interface for number pattern which support sequences.
 */
interface SequentialNumberPatternInterface extends NumberPatternInterface {

  /**
   * Gets the initial sequence for the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\commerce_number_pattern\Sequence
   *   The initial sequence.
   */
  public function getInitialSequence(ContentEntityInterface $entity);

  /**
   * Gets the current sequence for the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\commerce_number_pattern\Sequence|null
   *   The current sequence, or NULL if none found.
   */
  public function getCurrentSequence(ContentEntityInterface $entity);

  /**
   * Gets the next sequence for the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\commerce_number_pattern\Sequence
   *   The next sequence.
   */
  public function getNextSequence(ContentEntityInterface $entity);

  /**
   * Resets the sequence.
   */
  public function resetSequence();

}
