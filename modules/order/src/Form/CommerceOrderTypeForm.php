<?php

/**
 * @file
 * Contains Drupal\commerce_order\Form\CommerceOrderTypeForm.
 */

namespace Drupal\commerce_order\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CommerceOrderTypeForm extends EntityForm {

  /**
   * The order type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderTypeStorage;

  /**
   * Create an IndexForm object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $order_type_storage
   *   The order type storage.
   */
  public function __construct(EntityStorageInterface $order_type_storage) {
    // Setup object members.
    $this->orderTypeStorage = $order_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entity_manager */
   $entity_manager = $container->get('entity.manager');
   return new static($entity_manager->getStorage('commerce_order_type'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $order_type = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $order_type->label(),
      '#description' => $this->t('Label for the order type.'),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $order_type->id(),
      '#machine_name' => array(
        'exists' => array($this->orderTypeStorage(), 'load'),
        'source' => array('label'),
      ),
      '#disabled' => !$order_type->isNew(),
    );

    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $order_type->getDescription(),
      '#description' => $this->t('Description of this order type'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $order_type = $this->entity;

    try {
      $order_type->save();
      drupal_set_message($this->t('Saved the %label order type.', array(
        '%label' => $order_type->label(),
      )));
      $form_state->setRedirect('entity.commerce_order_type.list');
    }
    catch (\Exception $e) {
      $this->logger('commerce_order')->error($e);
      drupal_set_message($this->t('The %label order type was not saved.', array(
        '%label' => $order_type->label(),
      )), 'error');
      $form_state->setRebuild();
    }
  }

}
