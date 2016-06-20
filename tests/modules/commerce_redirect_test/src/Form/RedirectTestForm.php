<?php

namespace Drupal\commerce_redirect_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Tests throwing a redirect inside of a form.
 */
class RedirectTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_redirect_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Test that in a form with a subform will redirect properly.
    return \Drupal::formBuilder()->buildForm(RedirectTestSubForm::class, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing.
  }

}
