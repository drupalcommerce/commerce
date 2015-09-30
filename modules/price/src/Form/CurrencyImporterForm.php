<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Form\CurrencyImporterForm.
 */

namespace Drupal\commerce_price\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to import a currency.
 */
class CurrencyImporterForm extends FormBase {

  /**
   * The currency importer.
   *
   * @var \Drupal\commerce_price\CurrencyImporterInterface
   */
  protected $currencyImporter;

  /**
   * Constructs a new CurrencyImporterForm.
   */
  public function __construct() {
    $this->currencyImporter = \Drupal::service('commerce_price.currency_importer');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $currencies = $this->currencyImporter->getImportableCurrencies();

    if (!$currencies) {
      $form['message'] = [
        '#markup' => $this->t('All currencies are already imported.'),
      ];
    }
    else {
      $form['currencyCode'] = [
        '#type' => 'select',
        '#title' => $this->t('Currency code'),
        '#description' => $this->t('Please select the currency you would like to import.'),
        '#required' => TRUE,
        '#options' => $this->getCurrencyOptions($currencies),
      ];

      $form['actions']['#type'] = 'actions';
      $form['actions']['import'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#name' => 'import',
        '#value' => $this->t('Import'),
        '#submit' => ['::submitForm'],
      ];
      $form['actions']['import_new'] = [
        '#type' => 'submit',
        '#name' => 'import_and_new',
        '#value' => $this->t('Import and new'),
        '#submit' => ['::submitForm'],
      ];
    }

    return $form;
  }

  /**
   * Gets an options list for currencies.
   *
   * @param \Drupal\commerce_price\Entity\CurrencyInterface[] $currencies
   *   An array of currencies.
   *
   * @return array
   *   The list of options for a select widget.
   */
  public function getCurrencyOptions(array $currencies) {
    $options = [];
    foreach ($currencies as $currencyCode => $currency) {
      $options[$currencyCode] = $currency->getName();
    }
    asort($options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $currency = $this->currencyImporter->importCurrency(
      $values['currencyCode']
    );

    try {
      $currency->save();
      drupal_set_message(
        $this->t('Imported the %label currency.', ['%label' => $currency->label()])
      );
      $triggeringElement = $form_state->getTriggeringElement();
      if ($triggeringElement['#name'] == 'import_and_new') {
        $form_state->setRebuild();
      }
      else {
        $form_state->setRedirect('entity.commerce_currency.collection');
      }
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The %label currency was not imported.', ['%label' => $currency->label()]), 'error');
      $this->logger('commerce_price')->error($e);
      $form_state->setRebuild();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_price_currency_importer';
  }
}
