<?php

namespace Drupal\commerce_order\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\profile\Form\ProfileForm;

class ProfileAddressBookForm extends ProfileForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // Remove the details wrapper from the address widget.
    $form['address']['widget'][0]['#type'] = 'container';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->entity;
    $profile->save();

    $this->messenger()->addMessage($this->t('Saved the %label address.', ['%label' => $profile->label()]));
    $form_state->setRedirect('commerce_order.address_book.overview', [
      'user' => $profile->getOwnerId(),
    ]);
  }

}
