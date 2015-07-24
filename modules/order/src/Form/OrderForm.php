<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Form\OrderForm.
 */

namespace Drupal\commerce_order\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the commerce_order entity edit forms.
 */
class OrderForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->entity;
    $currentUser = $this->currentUser();

    $form['advanced'] = [
      '#type' => 'vertical_tabs',
      '#attributes' => ['class' => ['entity-meta']],
      '#weight' => 99,
    ];
    $form = parent::form($form, $form_state);

    $form['order_status'] = [
      '#type' => 'details',
      '#title' => t('Order status'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['order-form-order-status'],
      ],
      '#attached' => [
        'library' => ['commerce_order/drupal.commerce_order'],
      ],
      '#weight' => 90,
      '#optional' => TRUE,
    ];

    if (isset($form['status'])) {
      $form['status']['#group'] = 'order_status';
    }

    // Order authoring information for administrators.
    $form['author'] = [
      '#type' => 'details',
      '#title' => t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['order-form-author'],
      ],
      '#attached' => [
        'library' => ['commerce_order/drupal.commerce_order'],
      ],
      '#weight' => 91,
      '#optional' => TRUE,
    ];

    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'author';
    }

    if (isset($form['mail'])) {
      $form['mail']['#group'] = 'author';
    }

    if (isset($form['created'])) {
      $form['created']['#group'] = 'author';
    }

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('The order %label has been successfully saved.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_order.collection');
  }

}
