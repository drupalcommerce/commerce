<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Form\ProductVariationTypeForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\language\Entity\ContentLanguageSettings;

class ProductVariationTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $variationType = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $variationType->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $variationType->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_product\Entity\ProductVariationType::load',
      ],
      '#disabled' => !$variationType->isNew(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    ];

    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = array(
        '#type' => 'details',
        '#title' => t('Language settings'),
        '#group' => 'additional_settings',
      );

      $languageConfiguration = ContentLanguageSettings::loadByEntityTypeBundle('commerce_product_variation', $variationType->id());
      $form['language']['language_configuration'] = array(
        '#type' => 'language_configuration',
        '#entity_information' => array(
          'entity_type' => 'commerce_product_variation',
          'bundle' => $variationType->id(),
        ),
        '#default_value' => $languageConfiguration,
      );

      $form['#submit'][] = 'language_configuration_element_submit';
    }

    return $this->protectBundleIdElement($form);;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('The product variation type %label has been successfully saved.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_product_variation_type.collection');
  }

}
