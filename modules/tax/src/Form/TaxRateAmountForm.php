<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Form\TaxRateAmountForm.
 */

namespace Drupal\commerce_tax\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TaxRateAmountForm extends EntityForm {

  /**
   * The tax rate amount storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $taxRateAmountStorage;

  /**
   * The tax rate storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $taxRateStorage;

  /**
   * Creates a TaxRateAmountForm instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $taxRateAmountStorage
   *   The tax rate amount storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $taxRateStorage
   *   The tax rate storage.
   */
  public function __construct(EntityStorageInterface $taxRateAmountStorage, EntityStorageInterface $taxRateStorage) {
    $this->taxRateAmountStorage = $taxRateAmountStorage;
    $this->taxRateStorage = $taxRateStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = $container->get('entity_type.manager');

    return new static($entityTypeManager->getStorage('commerce_tax_rate_amount'), $entityTypeManager->getStorage('commerce_tax_rate'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $taxRateAmount = $this->entity;

    $form['rate'] = array(
      '#type' => 'hidden',
      '#value' => $taxRateAmount->getRate(),
    );
    $form['id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Machine name'),
      '#default_value' => $taxRateAmount->getId(),
      '#element_validate' => array('::validateId'),
      '#description' => $this->t('Only lowercase, underscore-separated letters allowed.'),
      '#pattern' => '[a-z_]+',
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['amount'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Amount'),
      '#default_value' => $taxRateAmount->getAmount(),
      '#element_validate' => array('::validateAmount'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['startDate'] = array(
      '#type' => 'date',
      '#title' => $this->t('Start date'),
      '#default_value' => $taxRateAmount->getStartDate(),
    );
    $form['endDate'] = array(
      '#type' => 'date',
      '#title' => $this->t('End date'),
      '#default_value' => $taxRateAmount->getEndDate(),
    );

    return $form;
  }

  /**
   * Validates the id field.
   */
  public function validateId(array $element, FormStateInterface $form_state, array $form) {
    $taxRateAmount = $this->getEntity();
    $id = $element['#value'];
    if (!preg_match('/[a-z_]+/', $id)) {
      $form_state->setError($element, $this->t('The machine name must be in lowercase, underscore-separated letters only.'));
    }
    elseif ($taxRateAmount->isNew()) {
      $loadedTaxRateAmounts = $this->taxRateAmountStorage->loadByProperties(array(
        'id' => $id,
      ));
      if ($loadedTaxRateAmounts) {
        $form_state->setError($element, $this->t('The machine name is already in use.'));
      }
    }
  }

  /**
   * Validates the amount field.
   */
  public function validateAmount(array $element, FormStateInterface $form_state, array $form) {
    if (!is_numeric($element['#value'])) {
      $form_state->setError($element, $this->t('The amount must be numeric.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $taxRateAmount = $this->entity;

    try {
      $taxRateAmount->save();
      drupal_set_message($this->t('Saved the %label tax rate.', array(
        '%label' => $taxRateAmount->label(),
      )));

      $taxRate = $this->taxRateStorage->load($taxRateAmount->getRate());
      try {
        if (!$taxRate->hasAmount($taxRateAmount)) {
          $taxRate->addAmount($taxRateAmount);
          $taxRate->save();
        }

        $form_state->setRedirect('entity.commerce_tax_rate_amount.collection', array(
          'commerce_tax_rate' => $taxRate->getId(),
        ));
      }
      catch (\Exception $e) {
        drupal_set_message($this->t('The %label tax rate was not saved.', array(
          '%label' => $taxRate->label(),
        )));
        $this->logger('commerce_tax')->error($e);
        $form_state->setRebuild();
      }

    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The %label tax rate amount was not saved.', array(
        '%label' => $taxRate->label()
      )), 'error');
      $this->logger('commerce_tax')->error($e);
      $form_state->setRebuild();
    }
  }

}
