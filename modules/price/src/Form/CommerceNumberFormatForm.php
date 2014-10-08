<?php

/**
 * @file
 * Contains Drupal\commerce_price\Form\CommerceNumberFormatForm.
 */

namespace Drupal\commerce_price\Form;

use CommerceGuys\Intl\NumberFormat\NumberFormatInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CommerceNumberFormatForm extends EntityForm {

  /**
   * The number format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $numberFormatStorage;

  /**
   * Creates a CommerceNumberFormatForm instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $number_format_storage
   *   The number format storage.
   */
  public function __construct(EntityStorageInterface $number_format_storage) {
    $this->numberFormatStorage = $number_format_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entity_manager */
    $entity_manager = $container->get('entity.manager');

    return new static($entity_manager->getStorage('commerce_number_format'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \CommerceGuys\Intl\NumberFormat\NumberFormatInterface $number_format */
    $number_format = $this->entity;

    $form['locale'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Locale'),
      '#description' => t('A unique machine-readable name. Can only contain letters and dashes'),
      '#default_value' => $number_format->getLocale(),
      '#placeholder' => 'en-US',
      '#maxlength' => 10,
      '#size' => 10,
      '#required' => TRUE,
      '#machine_name' => array(
        'exists' => array($this->numberFormatStorage, 'load'),
        'replace_pattern' => '[^A-Za-z_]+',
      ),
      '#disabled' => !$number_format->isNew(),
    );
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 30,
      '#size' => 30,
      '#default_value' => $number_format->getName(),
      '#required' => TRUE,
    );
    $form['numberingSystem'] = array(
      '#type' => 'select',
      '#title' => $this->t('Numbering system'),
      '#maxlength' => 255,
      '#default_value' => $number_format->getNumberingSystem() ? $number_format->getNumberingSystem() : 'latn',
      '#options' => array(
        NumberFormatInterface::NUMBERING_SYSTEM_ARABIC => $this->t('Arabic'),
        NumberFormatInterface::NUMBERING_SYSTEM_ARABIC_EXTENDED => $this->t('Arabic Extended'),
        NumberFormatInterface::NUMBERING_SYSTEM_BENGALI => $this->t('Bengali'),
        NumberFormatInterface::NUMBERING_SYSTEM_DEVANAGARI => $this->t('Devanagari'),
        NumberFormatInterface::NUMBERING_SYSTEM_LATIN => $this->t('Latin')
      ),
      '#required' => TRUE,
    );
    $form['decimalSeparator'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Decimal separator'),
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => $number_format->getDecimalSeparator() ? $number_format->getDecimalSeparator() : '.',
      '#required' => TRUE,
    );
    $form['groupingSeparator'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Grouping separator'),
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => $number_format->getGroupingSeparator() ? $number_format->getGroupingSeparator() : ',',
      '#required' => TRUE,
    );
    $form['plusSign'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Plug sign'),
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => $number_format->getPlusSign() ? $number_format->getPlusSign() : '=',
      '#required' => TRUE,
    );
    $form['minusSign'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Minus sign'),
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => $number_format->getMinusSign() ? $number_format->getMinusSign() : '-',
      '#required' => TRUE,
    );
    $form['percentSign'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Percent sign'),
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => $number_format->getPercentSign() ? $number_format->getPercentSign() : '%',
      '#required' => TRUE,
    );
    $form['decimalPattern'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Decimal pattern'),
      '#maxlength' => 30,
      '#size' => 30,
      '#default_value' => $number_format->getDecimalPattern(),
      '#required' => TRUE,
    );
    $form['percentPattern'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Percent pattern'),
      '#maxlength' => 30,
      '#size' => 30,
      '#default_value' => $number_format->getPercentPattern(),
      '#required' => TRUE,
    );
    $form['currencyPattern'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Currency pattern'),
      '#maxlength' => 30,
      '#size' => 30,
      '#default_value' => $number_format->getCurrencyPattern(),
      '#required' => TRUE,
    );
    $form['accountingCurrencyPattern'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Accounting currency pattern'),
      '#maxlength' => 30,
      '#size' => 30,
      '#default_value' => $number_format->getAccountingCurrencyPattern(),
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $currency = $this->entity;

    try {
      $currency->save();
      drupal_set_message($this->t('Saved the %label number format.', array(
        '%label' => $currency->label(),
      )));
      $form_state->setRedirect('entity.commerce_number_format.list');
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The %label number format was not saved.', array('%label' => $currency->label())), 'error');
      $this->logger('commerce_price')->error($e);
      $form_state->setRebuild();
    }
  }

}
