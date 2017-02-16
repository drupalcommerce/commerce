<?php

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Builds the form to delete a product attribute.
 */
class ProductAttributeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting a product attribute will delete all of its values. This action cannot be undone.');
  }

}
