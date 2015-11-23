<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Form\ProductTypeForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductTypeForm extends BundleEntityFormBase {

  /**
   * The variation type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $variationTypeStorage;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Creates a new ProductTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager) {
    $this->variationTypeStorage = $entityTypeManager->getStorage('commerce_product_variation_type');
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $productType = $this->entity;
    $variationTypes = $this->variationTypeStorage->loadMultiple();
    $variationTypes = array_map(function($variationType) {
      return $variationType->label();
    }, $variationTypes);
    // Create an empty product to get the default status value.
    // @todo Clean up once https://www.drupal.org/node/2318187 is fixed.
    if ($this->operation == 'add') {
      $product = $this->entityManager->getStorage('commerce_product')->create(['type' => $productType->uuid()]);
    }
    else {
      $product = $this->entityManager->getStorage('commerce_product')->create(['type' => $productType->id()]);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $productType->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $productType->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_product\Entity\ProductType::load',
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $productType->getDescription(),
    ];
    $form['variationType'] = [
      '#type' => 'select',
      '#title' => $this->t('Product variation type'),
      '#default_value' => $productType->getVariationType(),
      '#options' => $variationTypes,
      '#required' => TRUE,
    ];
    $form['product_status'] = [
      '#type' => 'checkbox',
      '#title' => t('Publish new products of this type by default.'),
      '#default_value' => $product->isPublished(),
    ];
    $form['digital'] = [
      '#type' => 'checkbox',
      '#title' => t('Digital'),
      '#default_value' => $productType->isDigital(),
      '#description' => t('Products of this type represent digital services.')
    ];

    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => $this->t('Language settings'),
        '#group' => 'additional_settings',
      ];
      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'commerce_product',
          'bundle' => $productType->id(),
        ],
        '#default_value' => ContentLanguageSettings::loadByEntityTypeBundle('commerce_product', $productType->id()),
      ];
      $form['#submit'][] = 'language_configuration_element_submit';
    }

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();
    // Update the default value of the status field.
    $product = $this->entityTypeManager->getStorage('commerce_product')->create(['type' => $this->entity->id()]);
    $value = (bool) $form_state->getValue('product_status');
    if ($product->status->value != $value) {
      $fields = $this->entityFieldManager->getFieldDefinitions('commerce_product', $this->entity->id());
      $fields['status']->getConfig($this->entity->id())->setDefaultValue($value)->save();
      $this->entityFieldManager->clearCachedFieldDefinitions();
    }

    drupal_set_message($this->t('The product type %label has been successfully saved.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_product_type.collection');
    if ($status == SAVED_NEW) {
      commerce_product_add_stores_field($this->entity);
      commerce_product_add_body_field($this->entity);
      commerce_product_add_variations_field($this->entity);
    }
  }

}
