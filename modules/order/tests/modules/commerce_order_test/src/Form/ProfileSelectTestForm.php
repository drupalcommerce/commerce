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
    $form['profile'] = [
      '#type' => 'commerce_profile_select',
      '#title' => $this->t('Profile'),
      '#default_value' => NULL,
      '#profile_type' => 'customer',
      '#owner_uid' => \Drupal::currentUser()->id(),
      '#available_countries' => ['HU', 'FR', 'US', 'RS', 'DE'],
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
    $profile = $form_state->getValue('profile');
    drupal_set_message($this->t('Profile selected: :label', [':label' => $profile->label()]));
  }

}
