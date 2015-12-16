<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Form\OrderAddForm.
 */

namespace Drupal\commerce_order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\user\Entity\User;

/**
 * Form controller for creating new commerce_order entities.
 */
class OrderAddForm extends FormBase {

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

    // Select the order type.
    $form['type'] = [
      '#type' => 'entity_select',
      '#title' => $this->t('Order type'),
      '#target_type' => 'commerce_order_type',
      '#required' => TRUE,
    ];

    // Select the order store.
    $form['store_id'] = [
      '#type' => 'entity_select',
      '#title' => $this->t('Store'),
      '#target_type' => 'commerce_store',
      '#required' => TRUE,
    ];

    // Customer user data for the order.
    $form['customer'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Customer information'),
      '#prefix' => '<div id="customer-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    // Select the user type for the customer.
    $customer_user_select_default = !empty($form_state->getValue(['customer_user_type'])) ? $form_state->getValue(['customer_user_type']) : 'existing';

    $form['customer']['customer_user_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Order for'),
      '#attributes' => [
        'class' => ['container-inline'],
      ],
      '#required' => TRUE,
      '#options' => [
        'existing' => $this->t('Existing customer'),
        'new' => $this->t('New customer'),
      ],
      '#default_value' => $customer_user_select_default,
      '#ajax' => [
        'callback' => [$this, 'validateCustomerUserType'],
        'wrapper' => 'customer-fieldset-wrapper',
      ],
    ];
    // Existing user - User autocomplete.
    if ($customer_user_select_default == 'existing') {
      $form['customer']['uid'] = [
        '#type' => 'entity_autocomplete',
        '#title' => t('Search'),
        '#attributes' => [
          'class' => ['container-inline'],
        ],
        '#placeholder' => $this->t('Search by customer username or email address'),
        '#target_type' => 'user',
        '#selection_settings' => [
          'match_operator' => 'CONTAINS',
          'include_anonymous' => FALSE,
        ],
      ];
    }
    // Anonymous user, ask for email.
    else {
      $form['customer']['uid'] = [
        '#type' => 'value',
        '#value' => 0,
      ];
      $form['customer']['mail'] = [
        '#type' => 'email',
        '#title' => t('Email'),
        '#required' => TRUE,
      ];
      $form['customer']['password'] = [
        '#type' => 'container',
      ];
      $form['customer']['password']['generate'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Generate customer password'),
        '#default_value' => 1,
      ];
      // We have to wrap the password_confirm element in order for #states
      // to work proper. See https://www.drupal.org/node/1427838.
      $form['customer']['password']['password_confirm_wrapper'] = [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            ':input[name="generate"]' => ['checked' => FALSE],
          ],
        ],
      ];
      // We cannot make this required due to HTML5 validation.
      $form['customer']['password']['password_confirm_wrapper']['pass'] = [
        '#type' => 'password_confirm',
        '#size' => 25,
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Create'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Rebuild form for customer user type selection.
   */
  public function validateCustomerUserType(array $form, FormStateInterface $form_state) {
    return $form['customer'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Create new order.
    $values = $form_state->getValues();
    // For existing user, use its email for the order.
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
