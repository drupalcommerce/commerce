<?php

/**
 * @file
 * Contains Drupal\commerce_price\Form\CommerceCurrencyForm.
 */

namespace Drupal\commerce_price\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CommerceCurrencyForm extends EntityForm {

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
   * Create an CommerceCurrencyForm object.
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
    $currency = $this->entity;

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $currency->getName(),
      '#required' => TRUE,
    );
    $form['currencyCode'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Currency code'),
      '#default_value' => $currency->getCurrencyCode(),
      '#machine_name' => array(
        'exists' => array($this->getStorage(), 'load'),
        'replace_pattern' => '[^A-Za-z0-9_]+',
      ),
      '#disabled' => !$currency->isNew(),
    );
    $form['numericCode'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Numeric code'),
      '#maxlength' => 255,
      '#default_value' => $currency->getNumericCode(),
      '#required' => TRUE,
    );
    $form['symbol'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Symbol'),
      '#maxlength' => 255,
      '#default_value' => $currency->getSymbol(),
      '#required' => TRUE,
    );
    $form['decimals'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Decimals'),
      '#maxlength' => 255,
      '#default_value' => $currency->getDecimals(),
      '#description' => $this->t('The number of digits after the decimal sign.'),
      '#required' => TRUE,
    );
    $form['status'] = array(
      '#default_value' => $currency->status(),
      '#title' => $this->t('Enabled'),
      '#type' => 'checkbox',
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
    return $this->entityManager->getStorage('commerce_currency');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $currency = $this->entity;

    try {
      $currency->save();
      drupal_set_message($this->t('Saved the %label currency.', array(
        '%label' => $currency->label(),
      )));
      $form_state->setRedirect('entity.commerce_currency.list');
    }
    catch (\Exception $e) {
      watchdog_exception('commerce_price', $e);
      drupal_set_message($this->t('The %label currency was not saved.', array(
        '%label' => $currency->label(),
      )), 'error');
      $form_state->setRebuild();
    }
  }

}
