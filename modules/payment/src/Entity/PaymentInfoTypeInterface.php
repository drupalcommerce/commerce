<?php

/**
 * @file
 * Contains \Drupal\commerce_payment\Entity\PaymentInfoTypeInterface.
 */

namespace Drupal\commerce_payment\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Commerce Payment Information type entity.
 */
interface PaymentInfoTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the payment information type description.
   *
   * @return string
   *   The payment information type description.
   */
  public function getDescription();

  /**
   * Sets the description of the payment information type.
   *
   * @param string $description
   *   The new description.
   *
   * @return $this
   */
  public function setDescription($description);

}
