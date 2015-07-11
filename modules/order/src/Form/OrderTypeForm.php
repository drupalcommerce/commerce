<?php

/**
 * @file
 * Contains Drupal\commerce_order\Form\OrderTypeForm.
 */

namespace Drupal\commerce_order\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderTypeForm extends EntityForm {

  /**
   * The order type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderTypeStorage;

  /**
   * Create an OrderTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $orderTypeStorage
   *   The order type storage.
   */
  public function __construct(EntityStorageInterface $orderTypeStorage) {
    $this->orderTypeStorage = $orderTypeStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entityManager */
   $entityManager = $container->get('entity.manager');

   return new static($entityManager->getStorage('commerce_order_type'));
 }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $orderType = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $orderType->label(),
      '#description' => $this->t('Label for the order type.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $orderType->id(),
      '#machine_name' => [
        'exists' => [$this->orderTypeStorage, 'load'],
        'source' => ['label'],
      ],
      '#disabled' => !$orderType->isNew(),
    ];
    $form['description'] = [
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $orderType->getDescription(),
      '#description' => $this->t('Description of this order type'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $orderType = $this->entity;
    $orderType->save();
    drupal_set_message($this->t('Saved the %label order type.', [
      '%label' => $orderType->label(),
    ]));
    $form_state->setRedirect('entity.commerce_order_type.collection');
  }

}
