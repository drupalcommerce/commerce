<?php

namespace Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the interface for number patterns.
 *
 * @see \Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\SequentialNumberPatternInterface
 */
interface NumberPatternInterface extends ConfigurableInterface, PluginInspectionInterface {

  /**
   * Generates a number for the given content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return string
   *   The generated number.
   */
  public function generate(ContentEntityInterface $entity);

}
