<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Form\CommerceTaxTypeImporterForm.
 */

namespace Drupal\commerce_tax\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Contribute form.
 */
class CommerceTaxTypeImporterForm extends FormBase {

  /**
   * The tax type importer.
   *
   * @var \Drupal\commerce_tax\CommerceTaxTypeImporterInterface
   */
  protected $taxTypeImporter;

  /**
   * Constructs a new CommerceTaxTypeImporterForm.
   */
  public function __construct() {
    $tax_type_factory = \Drupal::service('commerce_tax.tax_type_importer_factory');
    $this->taxTypeImporter = $tax_type_factory->createInstance();
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
    $tax_types = $this->taxTypeImporter->getImportableTaxTypes();

    if (!$tax_types) {
      $form['message'] = array(
        '#markup' => $this->t('All tax types are already imported'),
      );
      return $form;
    }

    $form['tax_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Tax type'),
      '#description' => $this->t('Please select the tax type you would like to import.'),
      '#required' => TRUE,
      '#options' => $this->getTaxTypeOptions($tax_types),
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

    return $form;
  }

  /**
   * Returns an options list for tax types.
   *
   * @param TaxTypeInterface[] $tax_types
   *   An array of tax types.
   *
   * @return array
   *   The list of options for a select widget.
   */
  public function getTaxTypeOptions($tax_types) {
    $options = array();
    foreach ($tax_types as $tax_type) {
      $options[$tax_type->getId()] = $tax_type->getName();
    }
    asort($options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $tax_type = $this->taxTypeImporter->createTaxType($values['tax_type']);

    try {
      $tax_type->save();
      drupal_set_message(
        $this->t('Imported the %label tax type.', array('%label' => $tax_type->label()))
      );
      $triggering_element['#name'] = $form_state->getTriggeringElement();
      if ($triggering_element['#name'] == 'import_and_new') {
        $form_state->setRebuild();
      }
      else {
        $form_state->setRedirect('entity.commerce_tax_type.list');
      }
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The %label tax type was not imported.', array('%label' => $tax_type->label())), 'error');
      $this->logger('commerce_tax')->error($e);
      $form_state->setRebuild();
    }
  }

}
