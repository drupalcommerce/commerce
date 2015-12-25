<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Form\TaxTypeImporterForm.
 */

namespace Drupal\commerce_tax\Form;

use Drupal\commerce_tax\Entity\TaxTypeInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contribute form.
 */
class TaxTypeImporterForm extends FormBase {

  /**
   * The tax type importer.
   *
   * @var \Drupal\commerce_tax\TaxTypeImporterInterface
   */
  protected $taxTypeImporter;

  /**
   * Constructs a new TaxTypeImporterForm.
   */
  public function __construct() {
    $taxTypeFactory = \Drupal::service('commerce_tax.tax_type_importer_factory');
    $this->taxTypeImporter = $taxTypeFactory->createInstance();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_tax_type_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $taxTypes = $this->taxTypeImporter->getImportableTaxTypes();

    if (!$taxTypes) {
      $form['message'] = [
        '#markup' => $this->t('All tax types are already imported'),
      ];
      return $form;
    }

    $form['tax_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Tax type'),
      '#description' => $this->t('Please select the tax type you would like to import.'),
      '#required' => TRUE,
      '#options' => $this->getTaxTypeOptions($taxTypes),
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

    return $form;
  }

  /**
   * Gets an options list for tax types.
   *
   * @param TaxTypeInterface[] $taxTypes
   *   An array of tax types.
   *
   * @return array
   *   The list of options for a select widget.
   */
  public function getTaxTypeOptions($taxTypes) {
    $options = [];
    foreach ($taxTypes as $taxType) {
      $options[$taxType->getId()] = $taxType->getName();
    }
    asort($options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $taxType = $this->taxTypeImporter->createTaxType($values['tax_type']);

    try {
      $taxType->save();
      drupal_set_message(
        $this->t('Imported the %label tax type.', ['%label' => $taxType->label()])
      );
      $triggeringElement['#name'] = $form_state->getTriggeringElement();
      if ($triggeringElement['#name'] == 'import_and_new') {
        $form_state->setRebuild();
      }
      else {
        $form_state->setRedirect('entity.commerce_tax_type.collection');
      }
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The %label tax type was not imported.', ['%label' => $taxType->label()]), 'error');
      $this->logger('commerce_tax')->error($e);
      $form_state->setRebuild();
    }
  }

}
