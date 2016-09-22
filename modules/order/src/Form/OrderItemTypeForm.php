<?php

namespace Drupal\commerce_order\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;

class OrderItemTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $order_item_type = $this->entity;
    // Prepare the list of purchasable entity types.
    $entity_types = $this->entityTypeManager->getDefinitions();
    $purchasable_entity_types = array_filter($entity_types, function ($entity_type) {
      return $entity_type->isSubclassOf('\Drupal\commerce\PurchasableEntityInterface');
    });
    $purchasable_entity_types = array_map(function ($entity_type) {
      return $entity_type->getLabel();
    }, $purchasable_entity_types);
    // Prepare the list of order types.
    $order_types = $this->entityTypeManager->getStorage('commerce_order_type')
      ->loadMultiple();
    $order_types = array_map(function ($order_type) {
      return $order_type->label();
    }, $order_types);

    $form['#tree'] = TRUE;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $order_item_type->label(),
      '#description' => $this->t('Label for the order item type.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $order_item_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_order\Entity\OrderItemType::load',
        'source' => ['label'],
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    ];
    $form['purchasableEntityType'] = [
      '#type' => 'select',
      '#title' => $this->t('Purchasable entity type'),
      '#default_value' => $order_item_type->getPurchasableEntityTypeId(),
      '#options' => $purchasable_entity_types,
      '#empty_value' => '',
      '#disabled' => !$order_item_type->isNew(),
    ];
    $form['orderType'] = [
      '#type' => 'select',
      '#title' => $this->t('Order type'),
      '#default_value' => $order_item_type->getOrderTypeId(),
      '#options' => $order_types,
      '#required' => TRUE,
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('Saved the %label order item type.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirect('entity.commerce_order_item_type.collection');
  }

}
