<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Form\OrderAddForm.
 */

namespace Drupal\commerce_order\Form;

use Drupal\commerce_order\OrderUserSelectTrait;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\user\Entity\User;

/**
 * Form controller for creating new commerce_order entities.
 */
class OrderAddForm extends FormBase {

  use OrderUserSelectTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'commerce_order_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['type'] = [
      '#type' => 'entity_select',
      '#title' => $this->t('Order type'),
      '#target_type' => 'commerce_order_type',
      '#required' => TRUE,
    ];
    $form['store_id'] = [
      '#type' => 'entity_select',
      '#title' => $this->t('Store'),
      '#target_type' => 'commerce_store',
      '#required' => TRUE,
    ];

    $this->addUserSelectElement($form, $form_state);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Create'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($values['customer_user_type'] == 'existing') {
      $values['mail'] = User::load($values['uid'])->getEmail();
    }
    else {
      $new_user = User::create([
        'name' => $values['mail'],
        'mail' => $values['mail'],
        'pass' => ($values['generate']) ? user_password() : $values['pass'],
        'status' => TRUE,
      ]);
      $new_user->save();
      $values['uid'] = $new_user->id();
    }

    $order = Order::create([
      'type' => $values['type'],
      'mail' => $values['mail'],
      'uid' => [$values['uid']],
      'store_id' => [$values['store_id']],
    ]);
    $order->save();
    // Redirect to the edit form to complete the order.
    $form_state->setRedirect('entity.commerce_order.edit_form', ['commerce_order' => $order->id()]);
  }
}
