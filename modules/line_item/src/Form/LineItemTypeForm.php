<?php

/**
 * @file
 * Contains Drupal\commerce_line_item\Form\LineItemTypeForm.
 */

namespace Drupal\commerce_line_item\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LineItemTypeForm extends EntityForm {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Create an LineItemTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entityManager) {
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $lineItemType = $this->entity;
    // Prepare the list of source entity types.
    $entityTypes = $this->entityManager->getDefinitions();
    $sourceEntityTypes = array_filter($entityTypes, function($entityType) {
      return $entityType->isSubclassOf('\Drupal\commerce\LineItemSourceInterface');
    });
    $sourceEntityTypes = array_map(function($entityType) {
      return $entityType->getLabel();
    }, $sourceEntityTypes);
    // Prepare the list of order types.
    $orderTypes = $this->entityManager->getStorage('commerce_order_type')->loadMultiple();
    $orderTypes = array_map(function($orderType) {
      return $orderType->label();
    }, $orderTypes);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $lineItemType->label(),
      '#description' => $this->t('Label for the line item type.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $lineItemType->id(),
      '#machine_name' => [
        'exists' => [$this->lineItemTypeStorage, 'load'],
        'source' => ['label'],
      ],
      '#disabled' => !$lineItemType->isNew()
    ];
    $form['sourceEntityType'] = [
      '#type' => 'select',
      '#title' => $this->t('Source entity type'),
      '#default_value' => $lineItemType->getSourceEntityType(),
      '#options' => $sourceEntityTypes,
      '#required' => TRUE,
    ];
    $form['orderType'] = [
      '#type' => 'select',
      '#title' => $this->t('Order type'),
      '#default_value' => $lineItemType->getOrderType(),
      '#options' => $orderTypes,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('Saved the %label line item type.', [
        '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirect('entity.commerce_line_item_type.collection');
  }

}
