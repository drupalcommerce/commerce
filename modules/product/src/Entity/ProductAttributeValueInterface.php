<?php

namespace Drupal\commerce_product\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the interface for product attribute values.
 */
interface ProductAttributeValueInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the attribute.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeInterface
   *   The attribute.
   */
  public function getAttribute();

  /**
   * Gets the attribute ID.
   *
   * The attribute id is also the bundle of the attribute value.
   *
   * @return string
   *   The attribute ID.
   */
  public function getAttributeId();

  /**
   * Gets the attribute value name.
   *
   * @return string
   *   The attribute value name.
   */
  public function getName();

  /**
   * Sets the attribute value name.
   *
   * @param string $name
   *   The attribute value name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the attribute value weight.
   *
   * @return int
   *   The attribute value weight.
   */
  public function getWeight();

  /**
   * Sets the attribute value weight.
   *
   * @param int $weight
   *   The attribute value weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Gets the attribute value creation timestamp.
   *
   * @return int
   *   The attribute value creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the attribute value creation timestamp.
   *
   * @param int $timestamp
   *   The attribute value creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

}
