<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Form\OrderTypeForm.
 */

namespace Drupal\commerce_order\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;

class OrderTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $order_type = $this->entity;
    $workflow_manager = \Drupal::service('plugin.manager.workflow');
    $workflows = $workflow_manager->getGroupedLabels('commerce_order');

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $order_type->label(),
      '#description' => $this->t('Label for the order type.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $order_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_order\Entity\OrderType::load',
        'source' => ['label'],
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    ];
    $form['workflow'] = [
      '#type' => 'select',
      '#title' => t('Workflow'),
      '#options' => $workflows,
      '#default_value' => $order_type->getWorkflow(),
      '#description' => $this->t('Used by all orders of this type.'),
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();
    drupal_set_message($this->t('Saved the %label order type.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_order_type.collection');

    if ($status == SAVED_NEW) {
      commerce_order_add_line_items_field($this->entity);
    }
  }

}
