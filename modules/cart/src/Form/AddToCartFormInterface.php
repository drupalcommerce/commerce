<?php

namespace Drupal\commerce_cart\Form;

use Drupal\Core\Entity\ContentEntityFormInterface;

/**
 * Provides the add to cart form interface.
 *
 * Extends the regular interface to allow the form ID to be overriden.
 * By default the form ID is suffixed with the order item's purchasable
 * entity ID, to achieve uniqueness. Callers can replace that form ID with
 * a more stable one, or to handle the case where the order item has no
 * purchasable entity, but multiple form instances are still desired.
 */
interface AddToCartFormInterface extends ContentEntityFormInterface {

  /**
   * Sets the form ID.
   *
   * @param string $form_id
   *   The form ID.
   *
   * @return $this
   */
  public function setFormId($form_id);

}
