<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Form\OrderForm.
 */

namespace Drupal\commerce_order\Form;

use Drupal\Component\Utility\Html;
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

    $form['#theme'] = 'commerce_order_edit_form';

    $form['advanced'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['entity-meta']],
      '#weight' => 99,
    ];
    $form = parent::form($form, $form_state);

    $form['order_information'] = [
      '#type' => 'details',
      '#title' => t('Order information'),
      '#group' => 'advanced',
      '#open' => TRUE,
      '#attributes' => [
        'class' => ['order-form-order-information'],
      ],
      '#weight' => 90,
    ];

    if (isset($form['store_id'])) {
      $form['store_id']['#group'] = 'order_information';
    }
    else {
      // @todo: This should be hidden if only one store.
      $form['order_information']['store'] = $this->fieldAsReadOnly(t('Store'), $order->getStore()->label());
    }

    if (isset($form['state'])) {
      $form['state']['#group'] = 'order_information';
    }
    else {
      $form['order_information']['state'] = $this->fieldAsReadOnly(t('State'), $order->getState()->getLabel());
    }

    if (isset($form['created'])) {
      $form['created']['#group'] = 'order_information';
    }
    else {
      // @todo: We need to define a "Placed" timestamp and replace this with it.
      $created = \Drupal::service('date.formatter')->format($order->getCreatedTime(), 'short');
      $form['order_information']['created'] = $this->fieldAsReadOnly(t('Created'), $created);
    }

    // Order customer information for administrators.
    $form['customer'] = [
      '#type' => 'details',
      '#title' => t('Customer information'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['order-form-author'],
      ],
      '#attached' => [
        'library' => ['commerce_order/drupal.commerce_order'],
      ],
      '#weight' => 91,
    ];

    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'customer';
    }
    else {
      $user_link = $order->getOwner()->toLink($order->getOwner()->getDisplayName())->toString();
      $form['customer']['uid'] = $this->fieldAsReadOnly(t('Customer'), $user_link);
    }

    if (isset($form['mail'])) {
      $form['mail']['#group'] = 'customer';
    }
    else {
      $form['customer']['mail'] = $this->fieldAsReadOnly(t('Email'), $order->getEmail());
    }

    $form['#attached']['library'][] = 'commerce_order/form';

    return $form;
  }

  /**
   * Returns Form API array for displaying entity values in the form.
   *
   * @param string $label
   *    The value label to be displayed.
   * @param string $value
   *    The value to be displayed.
   *
   * @return array
   *   Form API element
   */
  protected function fieldAsReadOnly($label, $value) {
    return [
      '#type' => 'item',
      '#wrapper_attributes' => array('class' => array(Html::cleanCssIdentifier($label), 'container-inline')),
      '#markup' => '<h4 class="label inline">' . $label . '</h4> ' . $value,
    ];
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
