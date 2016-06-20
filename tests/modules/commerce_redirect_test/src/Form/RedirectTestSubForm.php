<?php

namespace Drupal\commerce_redirect_test\Form;

use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Tests throwing a redirect inside of a form.
 */
class RedirectTestSubForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_redirect_test_sub_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    throw new NeedsRedirectException('https://www.drupal.org');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing.
  }

}
