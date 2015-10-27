<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Form\CurrencyForm.
 */

namespace Drupal\commerce_price\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CurrencyForm extends EntityForm {

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * Creates a new CurrencyForm object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $currencyStorage
   *   The currency storage.
   */
  public function __construct(EntityStorageInterface $currencyStorage) {
    $this->currencyStorage = $currencyStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entityManager */
    $entityManager = $container->get('entity.manager');

    return new static($entityManager->getStorage('commerce_currency'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $currency = $this->entity;

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $currency->getName(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['currencyCode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Currency code'),
      '#default_value' => $currency->getCurrencyCode(),
      '#element_validate' => ['::validateCurrencyCode'],
      '#pattern' => '[A-Z]{3}',
      '#placeholder' => 'XXX',
      '#maxlength' => 3,
      '#size' => 3,
      '#disabled' => !$currency->isNew(),
      '#required' => TRUE,
    ];
    $form['numericCode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Numeric code'),
      '#default_value' => $currency->getNumericCode(),
      '#element_validate' => ['::validateNumericCode'],
      '#pattern' => '[\d]{3}',
      '#placeholder' => '999',
      '#maxlength' => 3,
      '#size' => 3,
      '#required' => TRUE,
    ];
    $form['symbol'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Symbol'),
      '#default_value' => $currency->getSymbol(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['fractionDigits'] = [
      '#type' => 'number',
      '#title' => $this->t('Fraction digits'),
      '#description' => $this->t('The number of digits after the decimal sign.'),
      '#default_value' => $currency->getFractionDigits() ?: 2,
      '#min' => 0,
      '#required' => TRUE,
    ];
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $currency->status(),
    ];

    return $form;
  }

  /**
   * Validates the currency code.
   */
  public function validateCurrencyCode(array $element, FormStateInterface &$form_state, array $form) {
    $currency = $this->getEntity();
    $currencyCode = $element['#value'];
    if (!preg_match('/^[A-Z]{3}$/', $currencyCode)) {
      $form_state->setError($element, $this->t('The currency code must consist of three uppercase letters.'));
    }
    elseif ($currency->isNew()) {
      $loadedCurrency = $this->currencyStorage->load($currencyCode);
      if ($loadedCurrency) {
        $form_state->setError($element, $this->t('The currency code is already in use.'));
      }
    }
  }

  /**
   * Validates the numeric code.
   */
  public function validateNumericCode(array $element, FormStateInterface &$form_state, array $form) {
    $currency = $this->getEntity();
    $numericCode = $element['#value'];
    if ($numericCode && !preg_match('/^\d{3}$/i', $numericCode)) {
      $form_state->setError($element, $this->t('The numeric code must consist of three digits.'));
    }
    elseif ($currency->isNew()) {
      $loadedCurrencies = $this->currencyStorage->loadByProperties([
        'numericCode' => $numericCode,
      ]);
      if ($loadedCurrencies) {
        $form_state->setError($element, $this->t('The numeric code is already in use.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $currency = $this->entity;
    $currency->save();
    drupal_set_message($this->t('Saved the %label currency.', [
      '%label' => $currency->label(),
    ]));
    $form_state->setRedirect('entity.commerce_currency.collection');
  }

}
