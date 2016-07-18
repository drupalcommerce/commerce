<?php

namespace Drupal\commerce_promotion\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the commerce_promotion entity edit forms.
 */
class PromotionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['end_date']['#states'] = [
      'visible' => [
        'input[name="has_end_date[value]"]' => ['checked' => FALSE]
      ],
    ];
    return $form;
  }

}
