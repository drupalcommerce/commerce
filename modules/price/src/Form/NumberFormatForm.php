<?php

/**
 * @file
 * Contains Drupal\commerce_price\Form\NumberFormatForm.
 */

namespace Drupal\commerce_price\Form;

use CommerceGuys\Intl\NumberFormat\NumberFormatInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NumberFormatForm extends EntityForm {

  /**
   * The number format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $numberFormatStorage;

  /**
   * Creates a NumberFormatForm instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $numberFormatStorage
   *   The number format storage.
   */
  public function __construct(EntityStorageInterface $numberFormatStorage) {
    $this->numberFormatStorage = $numberFormatStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entityManager */
    $entityManager = $container->get('entity.manager');

    return new static($entityManager->getStorage('commerce_number_format'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \CommerceGuys\Intl\NumberFormat\NumberFormatInterface $numberFormat */
    $numberFormat = $this->entity;

    $form['locale'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Locale'),
      '#description' => t('A unique machine-readable name. Can only contain letters and dashes'),
      '#default_value' => $numberFormat->getLocale(),
      '#placeholder' => 'en-US',
      '#maxlength' => 10,
      '#size' => 10,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this->numberFormatStorage, 'load'],
        'replace_pattern' => '[^A-Za-z_]+',
      ],
      '#disabled' => !$numberFormat->isNew(),
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 30,
      '#size' => 30,
      '#default_value' => $numberFormat->getName(),
      '#required' => TRUE,
    ];
    $form['numberingSystem'] = [
      '#type' => 'select',
      '#title' => $this->t('Numbering system'),
      '#maxlength' => 255,
      '#default_value' => $numberFormat->getNumberingSystem() ? $numberFormat->getNumberingSystem() : 'latn',
      '#options' => [
        NumberFormatInterface::NUMBERING_SYSTEM_ARABIC => $this->t('Arabic'),
        NumberFormatInterface::NUMBERING_SYSTEM_ARABIC_EXTENDED => $this->t('Arabic Extended'),
        NumberFormatInterface::NUMBERING_SYSTEM_BENGALI => $this->t('Bengali'),
        NumberFormatInterface::NUMBERING_SYSTEM_DEVANAGARI => $this->t('Devanagari'),
        NumberFormatInterface::NUMBERING_SYSTEM_LATIN => $this->t('Latin')
      ],
      '#required' => TRUE,
    ];
    $form['decimalSeparator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Decimal separator'),
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => $numberFormat->getDecimalSeparator() ? $numberFormat->getDecimalSeparator() : '.',
      '#required' => TRUE,
    ];
    $form['groupingSeparator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Grouping separator'),
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => $numberFormat->getGroupingSeparator() ? $numberFormat->getGroupingSeparator() : ',',
      '#required' => TRUE,
    ];
    $form['plusSign'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Plug sign'),
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => $numberFormat->getPlusSign() ? $numberFormat->getPlusSign() : '=',
      '#required' => TRUE,
    ];
    $form['minusSign'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minus sign'),
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => $numberFormat->getMinusSign() ? $numberFormat->getMinusSign() : '-',
      '#required' => TRUE,
    ];
    $form['percentSign'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Percent sign'),
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => $numberFormat->getPercentSign() ? $numberFormat->getPercentSign() : '%',
      '#required' => TRUE,
    ];
    $form['decimalPattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Decimal pattern'),
      '#maxlength' => 30,
      '#size' => 30,
      '#default_value' => $numberFormat->getDecimalPattern(),
      '#required' => TRUE,
    ];
    $form['percentPattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Percent pattern'),
      '#maxlength' => 30,
      '#size' => 30,
      '#default_value' => $numberFormat->getPercentPattern(),
      '#required' => TRUE,
    ];
    $form['currencyPattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Currency pattern'),
      '#maxlength' => 30,
      '#size' => 30,
      '#default_value' => $numberFormat->getCurrencyPattern(),
      '#required' => TRUE,
    ];
    $form['accountingCurrencyPattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Accounting currency pattern'),
      '#maxlength' => 30,
      '#size' => 30,
      '#default_value' => $numberFormat->getAccountingCurrencyPattern(),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $currency = $this->entity;

    try {
      $currency->save();
      drupal_set_message($this->t('Saved the %label number format.', [
        '%label' => $currency->label(),
      ]));
      $form_state->setRedirect('entity.commerce_number_format.collection');
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The %label number format was not saved.', ['%label' => $currency->label()]), 'error');
      $this->logger('commerce_price')->error($e);
      $form_state->setRebuild();
    }
  }

}
