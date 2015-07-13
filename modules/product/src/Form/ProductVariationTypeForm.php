<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Form\ProductVariationTypeForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

class ProductVariationTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $variationType = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $variationType->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $variationType->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_product\Entity\ProductVariationType::load',
      ],
      '#disabled' => !$variationType->isNew(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('The product variation type %label has been successfully saved.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_product_variation_type.collection');
  }

}
