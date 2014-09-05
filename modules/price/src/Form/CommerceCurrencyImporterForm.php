<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Form\CommerceCurrencyImporterForm.
 */

namespace Drupal\commerce_price\Form;

use CommerceGuys\Intl\Currency\CurrencyInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use SebastianBergmann\Exporter\Exception;

/**
 * Builds the form to import a currency.
 */
class CommerceCurrencyImporterForm extends FormBase {

  /**
   * @var \Drupal\commerce_price\CurrencyImporterInterface
   */
  protected $currencyImporter;

  public function __construct() {
    $this->currencyImporter = \Drupal::service('commerce_price.currency_importer');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $currencies = $this->currencyImporter->getImportableCurrencies();

    if (!$currencies) {
      $form['message'] = array(
        '#markup' => $this->t('All currencies are already imported.'),
      );
    }
    else {
      $form['currency_code'] = array(
        '#type' => 'select',
        '#title' => $this->t('Currency code'),
        '#description' => $this->t('Please select the currency you would like to import.'),
        '#required' => TRUE,
        '#options' => $this->getCurrencyOptions($currencies),
      );

      $form['actions']['#type'] = 'actions';
      $form['actions']['import'] = array(
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#name' => 'import',
        '#value' => $this->t('Import'),
        '#submit' => array('::submitForm'),
      );
      $form['actions']['import_new'] = array(
        '#type' => 'submit',
        '#name' => 'import_and_new',
        '#value' => $this->t('Import and new'),
        '#submit' => array('::submitForm'),
      );
    }

    return $form;
  }

  /**
   * Returns an options list for currencies.
   *
   * @param CurrencyInterface[] $currencies
   *   An array of currencies.
   * @return array
   *   The list of options for a select widget.
   */
  public function getCurrencyOptions(array $currencies) {
    $options = array();
    foreach ($currencies as $currency_code => $currency) {
      $options[$currency_code] = $currency->getName();
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
      $values['currency_code']
    );

    try {
      $currency->save();
      drupal_set_message(
        $this->t('Imported the %label currency.', array('%label' => $currency->label()))
      );
      if ($form_state['triggering_element']['#name'] == 'import_and_new') {
        $form_state->setRebuild();
      }
      else {
        $form_state->setRedirect('entity.commerce_currency.list');
      }
    } catch (Exception $e) {
      drupal_set_message(
        $this->t(
          'The %label currency was not imported.',
          array('%label' => $currency->label())),
        'error');
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
