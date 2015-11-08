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
  protected $currencyImporter;

  /**
   * Creates a new CurrencyImportForm object.
   *
   * @param \Drupal\commerce_price\CurrencyImporterInterface $currencyImporter
   *   The currency importer.
   */
  public function __construct(CurrencyImporterInterface $currencyImporter) {
    $this->currencyImporter = $currencyImporter;
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
    $currencies = $this->currencyImporter->getImportable();
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
    $currencyCode = $form_state->getValue('currency_code');
    $currency = $this->currencyImporter->import($currencyCode);
    drupal_set_message($this->t('Imported the %label currency.', ['%label' => $currency->label()]));
    $form_state->setRebuild();
  }

}
