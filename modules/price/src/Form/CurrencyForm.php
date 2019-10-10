<?php

namespace Drupal\commerce_price\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class CurrencyForm extends EntityForm {

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Creates a new CurrencyForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The currency storage.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->storage = $entity_type_manager->getStorage('commerce_currency');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    return new static($entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency */
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
      '#pattern' => '[A-Z]+',
      '#placeholder' => 'XXX',
      '#maxlength' => 4,
      '#size' => 5,
      '#disabled' => !$currency->isNew(),
      '#required' => TRUE,
    ];
    $iso_4217_url = Url::fromUri('https://en.wikipedia.org/wiki/ISO_4217')->toString();
    $form['numericCode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Numeric code'),
      '#description' => $this->t('The three digit code, as defined by <a href=":url" target="_blank">ISO 4217</a>.', [':url' => $iso_4217_url]),
      '#default_value' => $currency->getNumericCode() ?: '000',
      '#element_validate' => ['::validateNumericCode'],
      '#pattern' => '[\d]{3}',
      '#maxlength' => 3,
      '#size' => 4,
      '#required' => TRUE,
    ];
    $form['symbol'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Symbol'),
      '#default_value' => $currency->getSymbol(),
      '#maxlength' => 4,
      '#size' => 4,
      '#required' => TRUE,
    ];
    $form['fractionDigits'] = [
      '#type' => 'select',
      '#title' => $this->t('Fraction digits'),
      '#description' => $this->t('The number of digits after the decimal sign.'),
      '#options' => range(0, 6),
      '#default_value' => $currency->getFractionDigits(),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * Validates the currency code.
   */
  public function validateCurrencyCode(array $element, FormStateInterface $form_state, array $form) {
    $currency = $this->getEntity();
    $currency_code = $element['#value'];
    if (!preg_match('/^[A-Z]+$/', $currency_code)) {
      $form_state->setError($element, $this->t('The currency code must consist only of uppercase letters.'));
    }
    elseif ($currency->isNew()) {
      $loaded_currency = $this->storage->load($currency_code);
      if ($loaded_currency) {
        $form_state->setError($element, $this->t('The currency code is already in use.'));
      }
    }
  }

  /**
   * Validates the numeric code.
   */
  public function validateNumericCode(array $element, FormStateInterface $form_state, array $form) {
    $currency = $this->getEntity();
    $numeric_code = $element['#value'];
    if ($numeric_code && !preg_match('/^\d{3}$/i', $numeric_code)) {
      $form_state->setError($element, $this->t('The numeric code must consist of three digits.'));
    }
    elseif ($currency->isNew()) {
      $loaded_currencies = $this->storage->loadByProperties([
        'numericCode' => $numeric_code,
      ]);
      if ($loaded_currencies) {
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
    $this->messenger()->addMessage($this->t('Saved the %label currency.', [
      '%label' => $currency->label(),
    ]));
    $form_state->setRedirect('entity.commerce_currency.collection');
  }

}
