<?php

/**
 * @file
 * Contains Drupal\commerce_product\Form\CommerceProductTypeForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

class CommerceProductTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
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
        'exists' => '\Drupal\commerce_product\Entity\CommerceProductType::load',
      ),
      '#disabled' => !$product_type->isNew(),
    );
    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $product_type->getDescription(),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    try {
      $this->entity->save();
      drupal_set_message($this->t('The product type %product_type_label has been successfully saved.', array('%product_type_label' => $this->entity->label())));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The product type %product_type_label could not be saved.', array('%product_type_label' => $this->entity->label())), 'error');
      $this->logger('commerce_product')->error($e);
    }

    $form_state->setRedirect('entity.commerce_product_type.list');
  }

}
