<?php

namespace Drupal\commerce_order\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a confirmation form for deleting a profile from the address book.
 */
class ProfileAddressBookDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the %label address?', [
      '%label' => $this->getEntity()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    $entity = $this->getEntity();

    return $this->t('The %label address has been deleted.', [
      '%label' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    /** @var \Drupal\profile\Entity\ProfileInterface $entity */
    $entity = $this->entity;

    return Url::fromRoute('commerce_order.address_book.overview', [
      'user' => $entity->getOwnerId(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

}
