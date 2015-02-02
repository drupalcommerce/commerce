<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Form\CommerceTaxRateForm.
 */

namespace Drupal\commerce_tax\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CommerceTaxRateForm extends EntityForm {

  /**
   * The tax rate storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $taxRateStorage;

  /**
   * The tax type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $taxTypeStorage;

  /**
   * Creates a CommerceTaxRateForm instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $taxRateStorage
   *   The tax rate storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $taxTypeStorage
   *   The tax type storage.
   */
  public function __construct(EntityStorageInterface $taxRateStorage, EntityStorageInterface $taxTypeStorage) {
    $this->taxRateStorage = $taxRateStorage;
    $this->taxTypeStorage = $taxTypeStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entityManager */
    $entityManager = $container->get('entity.manager');

    return new static($entityManager->getStorage('commerce_tax_rate'), $entityManager->getStorage('commerce_tax_type'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $taxRate = $this->entity;

    $form['type'] = array(
      '#type' => 'hidden',
      '#value' => $taxRate->getType(),
    );
    $form['id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Machine name'),
      '#default_value' => $taxRate->getId(),
      '#element_validate' => array('::validateId'),
      '#description' => $this->t('Only lowercase, underscore-separated letters allowed.'),
      '#pattern' => '[a-z_]+',
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $taxRate->getName(),
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['displayName'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Display name'),
      '#default_value' => $taxRate->getDisplayName(),
    );
    $form['default'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Default'),
      '#default_value' => $taxRate->isDefault(),
      '#element_validate' => array('::validateDefault'),
    );

    return $form;
  }

  /**
   * Validates the id field.
   */
  public function validateId(array $element, FormStateInterface $form_state, array $form) {
    $taxRate = $this->getEntity();
    $id = $element['#value'];
    if (!preg_match('/[a-z_]+/', $id)) {
      $form_state->setError($element, $this->t('The machine name must be in lowercase, underscore-separated letters only.'));
    }
    elseif ($taxRate->isNew()) {
      $loadedTaxRates = $this->taxRateStorage->loadByProperties(array(
        'id' => $id,
      ));
      if ($loadedTaxRates) {
        $form_state->setError($element, $this->t('The machine name is already in use.'));
      }
    }
  }

  /**
   * Validates that there is only one default per tax type.
   */
  public function validateDefault(array $element, FormStateInterface $form_state, array $form) {
    $taxRate = $this->getEntity();
    $default = $element['#value'];
    if ($default) {
      $loadedTaxRates = $this->taxRateStorage->loadByProperties(array(
        'type' => $form_state->getValue('type'),
      ));
      foreach ($loadedTaxRates as $rate) {
        if ($rate->getId() !== $taxRate->getId() && $rate->isDefault()) {
          $form_state->setError($element, $this->t('Tax rate %label is already the default.', array(
            '%label' => $rate->label(),
          )));
          break;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $taxRate = $this->entity;

    try {
      $taxRate->save();
      drupal_set_message($this->t('Saved the %label tax rate.', array(
        '%label' => $taxRate->label(),
      )));

      $taxType = $this->taxTypeStorage->load($taxRate->getType());
      try {
        if (!$taxType->hasRate($taxRate)) {
          $taxType->addRate($taxRate);
          $taxType->save();
        }

        $form_state->setRedirect('entity.commerce_tax_rate.collection', array(
          'commerce_tax_type' => $taxType->getId(),
        ));
      }
      catch (\Exception $e) {
        drupal_set_message($this->t('The %label tax type was not saved.', array(
          '%label' => $taxType->label(),
        )));
        $this->logger('commerce_tax')->error($e);
        $form_state->setRebuild();
      }

    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The %label tax rate was not saved.', array(
        '%label' => $taxRate->label()
      )), 'error');
      $this->logger('commerce_tax')->error($e);
      $form_state->setRebuild();
    }
  }

}
