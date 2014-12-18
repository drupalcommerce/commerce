<?php

/**
 * @file
 * Contains Drupal\commerce_tax\Form\CommerceTaxTypeForm.
 */

namespace Drupal\commerce_tax\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_tax\Entity\CommerceTaxType;

class CommerceTaxTypeForm extends EntityForm {

  /**
   * The tax type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $taxTypeStorage;

  /**
   * Creates a CommerceTaxTypeForm instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $tax_type_storage
   *   The tax type storage.
   */
  public function __construct(EntityStorageInterface $tax_type_storage) {
    $this->taxTypeStorage = $tax_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entity_manager */
    $entity_manager = $container->get('entity.manager');

    return new static($entity_manager->getStorage('commerce_tax_type'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $tax_type = $this->entity;

    $form['id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Machine name'),
      '#default_value' => $tax_type->getId(),
      '#element_validate' => array('::validateId'),
      '#description' => $this->t('Only lowercase, underscore-separated letters allowed.'),
      '#pattern' => '[a-z_]+',
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $tax_type->getName(),
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['compound'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compoundness'),
      '#default_value' => $tax_type->isCompound(),
    );
    $form['roundingMode'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Rounding mode'),
      '#default_value' => $tax_type->getRoundingMode(),
      '#options' => array(
        CommerceTaxType::ROUND_HALF_UP => $this->t('Round up'),
        CommerceTaxType::ROUND_HALF_DOWN => $this->t('Round down'),
        CommerceTaxType::ROUND_HALF_EVEN => $this->t('Round even'),
        CommerceTaxType::ROUND_HALF_ODD => $this->t('Round odd'),
      ),
      '#required' => TRUE,
    );
    $form['tag'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Tag'),
      '#default_value' => $tax_type->getTag(),
      '#element_validate' => array('::validateTag'),
      '#pattern' => '[a-zA-Z0-9]+',
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * Validates the id field.
   */
  public function validateId(array $element, FormStateInterface $form_state, array $form) {
    $tax_type = $this->getEntity();
    $id = $element['#value'];
    if (!preg_match('/[a-z_]+/', $id)) {
      $form_state->setError($element, $this->t('The machine name must be in lowercase, underscore-separated letters only.'));
    }
    elseif ($tax_type->isNew()) {
      $loaded_tax_types = $this->taxTypeStorage->loadByProperties(array(
        'id' => $id,
      ));
      if ($loaded_tax_types) {
        $form_state->setError($element, $this->t('The machine name is already in use.'));
      }
    }
  }

  /**
   * Validates the tag field.
   */
  public function validateTag(array $element, FormStateInterface $form_state, array $form) {
    $tag = $element['#value'];
    if (!preg_match('/[a-zA-Z0-9]+/', $tag)) {
      $form_state->setError($element, $this->t('The tag must be a single word.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $tax_type = $this->entity;

    try {
      $tax_type->save();
      drupal_set_message($this->t('Saved the %label tax type.', array(
        '%label' => $tax_type->label(),
      )));
      $form_state->setRedirect('entity.commerce_tax_type.list');
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The %label tax type was not saved.', array(
        '%label' => $tax_type->label()
      )), 'error');
      $this->logger('commerce_tax')->error($e);
      $form_state->setRebuild();
    }
  }

}
