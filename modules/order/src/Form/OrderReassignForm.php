<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Form\OrderReassignForm.
 */

namespace Drupal\commerce_order\Form;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\OrderUserSelectTrait;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Form controller for reassigning new commerce_order entity owners.
 */
class OrderReassignForm extends FormBase {

  use OrderUserSelectTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_order_reassign_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order_id = \Drupal::routeMatch()->getParameter('commerce_order');
    $order = Order::load($order_id);
    $form['order_id'] = [
      '#type' => 'value',
      '#value' => $order_id,
    ];

    $this->addUserSelectElement($form, $form_state);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Change customer'),
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

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::load($values['order_id']);
    $order->setEmail($values['mail']);
    $order->setOwnerId($values['uid']);
    $order->save();
    // Redirect to the edit form to complete the order.
    drupal_set_message($this->t('The order %label has been changed to customer %customer', [
      '%label' => $order->label(),
      '%customer' => $order->getOwner()->label(),
    ]));
    $form_state->setRedirect('entity.commerce_order.edit_form', ['commerce_order' => $order->id()]);
  }

}
