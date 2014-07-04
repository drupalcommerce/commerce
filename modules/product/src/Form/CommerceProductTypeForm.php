<?php

/**
 * @file
 * Contains Drupal\commerce_product\Form\CommerceProductTypeForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\EntityForm;

class CommerceProductTypeForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    $product_type = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $product_type->label(),
      '#description' => $this->t('Label for the product type.'),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $product_type->id(),
      '#machine_name' => array(
        'exists' => 'commerce_product_type_load',
      ),
      '#disabled' => !$product_type->isNew(),
    );

    // You will need additional form elements for your custom properties.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $product_type = $this->entity;
    $status = $product_type->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label product type.', array(
        '%label' => $product_type->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label product type was not saved.', array(
        '%label' => $product_type->label(),
      )));
    }

    $form_state['redirect'] = 'admin/commerce/products/types';
  }
}
