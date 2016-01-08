<?php

/**
 * @file
 * Contains \Drupal\commerce_order\OrderUserSelectTrait.
 */

namespace Drupal\commerce_order;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Trait that provides a form API element for selecting or creating a new user.
 */
trait OrderUserSelectTrait {

  /**
   * Adds the user select form elements to the provided Form API array.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addUserSelectElement(array &$form, FormStateInterface $form_state) {
    $customer_user_select_default = $form_state->getValue(['customer_user_type'], 'existing');

    $form['customer'] = [
      '#type' => 'fieldset',
      '#title' => t('Customer information'),
      '#prefix' => '<div id="customer-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['customer']['customer_user_type'] = [
      '#type' => 'radios',
      '#title' => t('Order for'),
      '#attributes' => [
        'class' => ['container-inline'],
      ],
      '#required' => TRUE,
      '#options' => [
        'existing' => t('Existing customer'),
        'new' => t('New customer'),
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
        '#placeholder' => t('Search by customer username or email address'),
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
        '#title' => t('Generate customer password'),
        '#default_value' => 1,
      ];
      // We have to wrap the password_confirm element in order for #states
      // to work properly. See https://www.drupal.org/node/1427838.
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
  }

  /**
   * Rebuild form for customer user type selection.
   */
  public function validateCustomerUserType(array $form, FormStateInterface $form_state) {
    return $form['customer'];
  }

}
