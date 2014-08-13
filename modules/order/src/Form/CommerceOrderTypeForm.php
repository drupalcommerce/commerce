<?php

/**
 * @file
 * Contains Drupal\commerce_order\Form\CommerceOrderTypeForm.
 */

namespace Drupal\commerce_order\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CommerceOrderTypeForm extends EntityForm {

  /**
   * The entity manager.
   *
   * This object members must be set to anything other than private in order for
   * \Drupal\Core\DependencyInjection\DependencySerialization to detected.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Create an IndexForm object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManager $entity_manager) {
    // Setup object members.
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
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
        'exists' => array($this->getTypeStorage(), 'load'),
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
   * Get the type storage controller.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   An instance of EntityStorageInterface.
   */
  protected function getTypeStorage() {
    return $this->entityManager->getStorage('commerce_order_type');
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
