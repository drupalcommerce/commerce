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
   * @param \Drupal\Core\Entity\EntityStorageInterface $line_item_type_storage
   *   The line_item type storage.
   */
  public function __construct(EntityStorageInterface $line_item_type_storage) {
    // Setup object members.
    $this->lineItemTypeStorage = $line_item_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entity_manager */
   $entity_manager = $container->get('entity.manager');
   return new static($entity_manager->getStorage('commerce_line_item_type'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $line_item_type = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $line_item_type->label(),
      '#description' => $this->t('Label for the line item type.'),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $line_item_type->id(),
      '#machine_name' => array(
        'exists' => array($this->line_itemTypeStorage, 'load'),
        'source' => array('label'),
      ),
      '#disabled' => !$line_item_type->isNew(),
    );

    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $line_item_type->getDescription(),
      '#description' => $this->t('Description of this line item type'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $line_item_type = $this->entity;

    try {
      $line_item_type->save();
      drupal_set_message($this->t('Saved the %label line item type.', array(
        '%label' => $line_item_type->label(),
      )));
      $form_state->setRedirect('entity.commerce_line_item_type.list');
    }
    catch (\Exception $e) {
      $this->logger('commerce_line_item')->error($e);
      drupal_set_message($this->t('The %label line item type was not saved.', array(
        '%label' => $line_item_type->label(),
      )), 'error');
      $form_state->setRebuild();
    }
  }

}
