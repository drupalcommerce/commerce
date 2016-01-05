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
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $variation_type->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $variation_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_product\Entity\ProductVariationType::load',
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    ];

    if (\Drupal::moduleHandler()->moduleExists('commerce_order')) {
      // Prepare a list of line item types used to purchase product variations.
      $line_item_type_storage = $this->entityTypeManager->getStorage('commerce_line_item_type');
      $line_item_types = $line_item_type_storage->loadMultiple();
      $line_item_types = array_filter($line_item_types, function($line_item_type) {
        return $line_item_type->getPurchasableEntityType() == 'commerce_product_variation';
      });
      $line_item_types = array_map(function ($line_item_type) {
        return $line_item_type->label();
      }, $line_item_types);

      $form['lineItemType'] = [
        '#type' => 'select',
        '#title' => $this->t('Line item type'),
        '#default_value' => $variation_type->getLineItemType(),
        '#options' => $line_item_types,
        '#empty_value' => '',
        '#required' => TRUE,
      ];
    }

    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => $this->t('Language settings'),
        '#group' => 'additional_settings',
      ];
      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'commerce_product_variation',
          'bundle' => $variation_type->id(),
        ],
        '#default_value' => ContentLanguageSettings::loadByEntityTypeBundle('commerce_product_variation', $variation_type->id()),
      ];
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
