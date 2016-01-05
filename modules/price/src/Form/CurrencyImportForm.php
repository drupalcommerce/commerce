<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Form\CurrencyImportForm.
 */

namespace Drupal\commerce_price\Form;

use Drupal\commerce_price\CurrencyImporterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for importing currencies from library definitions.
 */
class CurrencyImportForm extends FormBase {

  /**
   * The currency importer.
   *
   * @var \Drupal\commerce_price\CurrencyImporterInterface
   */
  protected $importer;

  /**
   * Creates a new CurrencyImportForm object.
   *
   * @param \Drupal\commerce_price\CurrencyImporterInterface $importer
   *   The currency importer.
   */
  public function __construct(CurrencyImporterInterface $importer) {
    $this->importer = $importer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('commerce_price.currency_importer'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_price_currency_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $currencies = $this->importer->getImportable();
    if (empty($currencies)) {
      $form['message'] = [
        '#markup' => $this->t('All currencies have already been imported.'),
      ];
    }
    else {
      $form['currency_code'] = [
        '#type' => 'select',
        '#title' => $this->t('Currency'),
        '#required' => TRUE,
        '#options' => $currencies,
      ];
      $form['actions']['#type'] = 'actions';
      $form['actions']['import'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#name' => 'import',
        '#value' => $this->t('Import'),
        '#submit' => ['::submitForm'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $currency_code = $form_state->getValue('currency_code');
    $currency = $this->importer->import($currency_code);
    drupal_set_message($this->t('Imported the %label currency.', ['%label' => $currency->label()]));
    $form_state->setRebuild();
  }

}
