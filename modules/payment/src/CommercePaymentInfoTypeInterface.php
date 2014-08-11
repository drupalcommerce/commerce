<?php

/**
 * @file
 * Contains \Drupal\commerce_payment\CommercePaymentInfoTypeInterface.
 */

namespace Drupal\commerce_payment;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Commerce Payment Information type entity.
 */
interface CommercePaymentInfoTypeInterface extends ConfigEntityInterface {

  /**
   * Returns the payment information type description.
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
