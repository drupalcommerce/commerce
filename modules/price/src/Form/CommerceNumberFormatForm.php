<?php

/**
 * @file
 * Contains Drupal\commerce_price\Form\CommerceNumberFormatForm.
 */

namespace Drupal\commerce_price\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CommerceNumberFormatForm extends EntityForm {

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
   * Create a CommerceCurrencyForm object.
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
    /** @var \CommerceGuys\Intl\NumberFormat\NumberFormatInterface $number_format */
    $number_format = $this->entity;

    $form['locale'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Locale'),
      '#maxlength' => 255,
      '#default_value' => $number_format->getLocale(),
      '#required' => TRUE,
      '#machine_name' => array(
        'exists' => array($this->getStorage(), 'load'),
        'replace_pattern' => '[^A-Za-z0-9_]+',
      ),
      '#disabled' => !$number_format->isNew(),
    );
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $number_format->getName(),
      '#required' => TRUE,
    );
    $form['numberingSystem'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Numbering system'),
      '#maxlength' => 255,
      '#default_value' => $number_format->getNumberingSystem() ? $number_format->getNumberingSystem() : 'latn',
      '#required' => TRUE,
    );
    $form['decimalSeparator'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Decimal separator'),
      '#maxlength' => 255,
      '#default_value' => $number_format->getDecimalSeparator() ? $number_format->getDecimalSeparator() : '.',
      '#required' => TRUE,
    );
    $form['groupingSeparator'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Grouping separator'),
      '#maxlength' => 255,
      '#default_value' => $number_format->getGroupingSeparator() ? $number_format->getGroupingSeparator() : ',',
      '#required' => TRUE,
    );
    $form['plusSign'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Plug sign'),
      '#maxlength' => 255,
      '#default_value' => $number_format->getPlusSign() ? $number_format->getPlusSign() : '=',
      '#required' => TRUE,
    );
    $form['minusSign'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Minus sign'),
      '#maxlength' => 255,
      '#default_value' => $number_format->getMinusSign() ? $number_format->getMinusSign() : '-',
      '#required' => TRUE,
    );
    $form['percentSign'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Percent sign'),
      '#maxlength' => 255,
      '#default_value' => $number_format->getPercentSign() ? $number_format->getPercentSign() : '%',
      '#required' => TRUE,
    );
    $form['decimalPattern'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Decimal pattern'),
      '#maxlength' => 255,
      '#default_value' => $number_format->getDecimalPattern(),
      '#required' => TRUE,
    );
    $form['percentPattern'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Percent pattern'),
      '#maxlength' => 255,
      '#default_value' => $number_format->getPercentPattern(),
      '#required' => TRUE,
    );
    $form['currencyPattern'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Currency pattern'),
      '#maxlength' => 255,
      '#default_value' => $number_format->getCurrencyPattern(),
      '#required' => TRUE,
    );
    $form['accountingCurrencyPattern'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Accounting currency pattern'),
      '#maxlength' => 255,
      '#default_value' => $number_format->getAccountingCurrencyPattern(),
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * Get the storage controller.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   An instance of EntityStorageInterface.
   */
  protected function getStorage() {
    return $this->entityManager->getStorage('commerce_number_format');
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
