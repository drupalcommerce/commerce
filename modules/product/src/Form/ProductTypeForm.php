<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Form\ProductTypeForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

class ProductTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $productType = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $productType->label(),
      '#description' => $this->t('Label for the product type.'),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $productType->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\commerce_product\Entity\ProductType::load',
      ),
      '#disabled' => !$productType->isNew(),
    );
    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $productType->getDescription(),
    );
    $form['digital'] = array(
      '#type' => 'checkbox',
      '#title' => t('Digital'),
      '#default_value' => $productType->isDigital(),
      '#description' => t('Products of this type represent digital services.')
    );
    $form['revision'] = array(
      '#type' => 'checkbox',
      '#title' => t('Create new revision'),
      '#default_value' => $productType->revision,
      '#description' => t('Create a new revision by default for this product type.')
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
      $form_state->setRedirect('entity.commerce_product_type.collection');
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The product type %product_type_label could not be saved.', array('%product_type_label' => $this->entity->label())), 'error');
      $this->logger('commerce_product')->error($e);
      $form_state->setRebuild();
    }
  }

}
