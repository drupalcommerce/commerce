<?php
/**
 * @file
 * Contains Drupal\commerce_payment\Form\PaymentInfoForm.
 */

namespace Drupal\commerce_payment\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the commerce_payment_info entity edit forms.
 */
class PaymentInfoForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form['advanced'] = array(
      '#type' => 'vertical_tabs',
      '#attributes' => array('class' => array('entity-meta')),
      '#weight' => 99,
    );
    $form = parent::form($form, $form_state);

    $form['payment_status'] = array(
      '#type' => 'details',
      '#title' => t('Payment status'),
      '#group' => 'advanced',
      '#attributes' => array(
        'class' => array('order-form-order-status'),
      ),
      '#attached' => array(
        'library' => array('commerce_order/drupal.commerce_order'),
      ),
      '#weight' => 90,
      '#optional' => TRUE,
    );

    if (isset($form['status'])) {
      $form['status']['#group'] = 'payment_status';
    }

    // Payment info authoring information for administrators.
    $form['author'] = array(
      '#type' => 'details',
      '#title' => t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => array(
        'class' => array('order-form-author'),
      ),
      '#attached' => array(
        'library' => array('commerce_order/drupal.commerce_order'),
      ),
      '#weight' => 91,
      '#optional' => TRUE,
    );

    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'author';
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
    drupal_set_message($this->t('The payment information %payment_info_label has been successfully saved.', array('%payment_info_label' => $this->entity->label())));
    $form_state->setRedirect('entity.commerce_payment_info.collection');
  }

}
