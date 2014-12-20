<?php
/**
 * @file
 * Definition of Drupal\commerce_line_item\Form\CommerceLineItemForm.
 */
namespace Drupal\commerce_line_item\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the commerce_line_item entity edit forms.
 */
class CommerceLineItemForm extends ContentEntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    try {
      $this->entity->save();
      drupal_set_message($this->t('The order %order_label has been successfully saved.', array('%order_label' => $this->entity->label())));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The order %order_label could not be saved.', array('%order_label' => $this->entity->label())), 'error');
      $this->logger('commerce_line_item')->error($e);
    }
    $form_state->setRedirect('entity.commerce_line_item.list');
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_line_item\Entity\CommerceLineItem $order */
    $order = $this->entity;
    $current_user = $this->currentUser();

    $form['advanced'] = array(
      '#type' => 'vertical_tabs',
      '#attributes' => array('class' => array('entity-meta')),
      '#weight' => 99,
    );
    $form = parent::form($form, $form_state);

    $form['order_status'] = array(
      '#type' => 'details',
      '#title' => t('Order status'),
      '#group' => 'advanced',
      '#attributes' => array(
        'class' => array('order-form-order-status'),
      ),
      '#attached' => array(
        'library' => array('commerce_line_item/drupal.commerce_line_item'),
      ),
      '#weight' => 90,
      '#optional' => TRUE,
    );

    if (isset($form['status'])) {
      $form['status']['#group'] = 'order_status';
    }

    $form['revision'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => $order->isNewRevision(),
      '#access' => $current_user->hasPermission('administer products'),
      '#group' => 'order_status',
      '#weight' => 10,
    );

    $form['revision_log'] += array(
      '#states' => array(
        'visible' => array(
          ':input[name="revision"]' => array('checked' => TRUE),
        ),
      ),
      '#group' => 'order_status',
    );

    // Order authoring information for administrators.
    $form['author'] = array(
      '#type' => 'details',
      '#title' => t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => array(
        'class' => array('order-form-author'),
      ),
      '#attached' => array(
        'library' => array('commerce_line_item/drupal.commerce_line_item'),
      ),
      '#weight' => 91,
      '#optional' => TRUE,
    );

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

}
