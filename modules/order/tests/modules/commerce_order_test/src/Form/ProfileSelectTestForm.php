<?php

namespace Drupal\commerce_order_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ProfileSelectTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_profile_select_element_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $profile_storage = \Drupal::getContainer()->get('entity_type.manager')->getStorage('profile');

    $profile = $profile_storage->create([
      'type' => 'customer',
      'uid' => \Drupal::currentUser()->id(),
    ]);

    $form['profile'] = [
      '#type' => 'commerce_profile_select',
      '#title' => $this->t('Profile'),
      '#default_value' => $profile,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message('Profile saved.');
  }

}
