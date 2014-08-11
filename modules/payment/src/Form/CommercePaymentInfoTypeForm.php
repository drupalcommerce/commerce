<?php

/**
 * @file
 * Contains Drupal\commerce_payment\Form\CommercePaymentInfoTypeForm.
 */

namespace Drupal\commerce_payment\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CommercePaymentInfoTypeForm extends EntityForm {

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
    $payment_information_type = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $payment_information_type->label(),
      '#description' => $this->t('Label for the payment information type.'),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $payment_information_type->id(),
      '#machine_name' => array(
        'exists' => array($this->getTypeStorage(), 'load'),
        'source' => array('label'),
      ),
      '#disabled' => !$payment_information_type->isNew(),
    );

    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $payment_information_type->getDescription(),
      '#description' => $this->t('Description of this payment information type'),
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
    return $this->entityManager->getStorage('commerce_payment_info_type');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $payment_information_type = $this->entity;

    try {
      $payment_information_type->save();
      drupal_set_message($this->t('Saved the %payment_info_type_label payment information type.', array(
        '%payment_info_type_label' => $payment_information_type->label(),
      )));
      $form_state->setRedirect('entity.commerce_payment_info_type.list');
    }
    catch (\Exception $e) {
      watchdog_exception('commerce_payment', $e);
      drupal_set_message($this->t('The %payment_info_type_label payment information type was not saved.', array(
        '%payment_info_type_label' => $payment_information_type->label(),
      )), 'error');
      $form_state->setRebuild();
    }
  }

}
