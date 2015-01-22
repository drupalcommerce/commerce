<?php

/**
 * @file
 * Contains Drupal\commerce_line_item\Form\CommerceLineItemTypeForm.
 */

namespace Drupal\commerce_line_item\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CommerceLineItemTypeForm extends EntityForm {

  /**
   * The line_item type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $lineItemTypeStorage;

  /**
   * Create an CommerceLineItemTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $lineItemTypeStorage
   *   The line_item type storage.
   */
  public function __construct(EntityStorageInterface $lineItemTypeStorage) {
    // Setup object members.
    $this->lineItemTypeStorage = $lineItemTypeStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entityManager */
   $entityManager = $container->get('entity.manager');
   return new static($entityManager->getStorage('commerce_line_item_type'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $lineItemType = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $lineItemType->label(),
      '#description' => $this->t('Label for the line item type.'),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $lineItemType->id(),
      '#machine_name' => array(
        'exists' => array($this->lineItemTypeStorage, 'load'),
        'source' => array('label'),
      ),
      '#disabled' => !$lineItemType->isNew(),
    );

    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $lineItemType->getDescription(),
      '#description' => $this->t('Description of this line item type'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $lineItemType = $this->entity;

    try {
      $lineItemType->save();
      drupal_set_message($this->t('Saved the %label line item type.', array(
        '%label' => $lineItemType->label(),
      )));
      $form_state->setRedirect('entity.commerce_line_item_type.list');
    }
    catch (\Exception $e) {
      $this->logger('commerce_line_item')->error($e);
      drupal_set_message($this->t('The %label line item type was not saved.', array(
        '%label' => $lineItemType->label(),
      )), 'error');
      $form_state->setRebuild();
    }
  }

}
