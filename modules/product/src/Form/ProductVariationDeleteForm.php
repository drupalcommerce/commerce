<?php

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Overrides messages to use "variation" instead of "product variation".
 *
 * This matches the terminology used on other variation routes, which omit
 * the "product" part because it's obvious from the context/url.
 */
class ProductVariationDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the %label variation?', [
      '%label' => $this->getEntity()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    $entity = $this->getEntity();
    return $this->t('The %label variation has been deleted.', [
      '%label' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function logDeletionMessage() {
    $entity = $this->getEntity();
    $this->logger($entity->getEntityType()->getProvider())->notice('The %label variation has been deleted.', [
      '%label' => $entity->label(),
    ]);
  }

}
