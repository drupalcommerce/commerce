<?php

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_product\Entity\ProductVariationType;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->variationTypeStorage = $entity_type_manager->getStorage('commerce_product_variation_type');
    $this->entityFieldManager = $entity_field_manager;
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
    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
    $product_type = $this->entity;
    $variation_types = $this->variationTypeStorage->loadMultiple();
    $variation_types = array_map(function($variation_type) {
      return $variation_type->label();
    }, $variation_types);
    // _new might be a machine name so let's use NEW.
    $variation_types = array_merge(['NEW' => $this->t('- Add new variation -')],$variation_types);
    // Create an empty product to get the default status value.
    // @todo Clean up once https://www.drupal.org/node/2318187 is fixed.
    if ($this->operation == 'add') {
      $product = $this->entityTypeManager->getStorage('commerce_product')->create(['type' => $product_type->uuid()]);
    }
    else {
      $product = $this->entityTypeManager->getStorage('commerce_product')->create(['type' => $product_type->id()]);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $product_type->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $product_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_product\Entity\ProductType::load',
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $product_type->getDescription(),
    ];
    $form['variationType'] = [
      '#type' => 'select',
      '#title' => $this->t('Product variation type'),
      '#default_value' => $product_type->getVariationTypeId(),
      '#options' => $variation_types,
      '#required' => TRUE,
      '#disabled' => !$product_type->isNew(),
    ];
    // New product variation type fields.
    $form['newVariationType'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('New product variation type'),
      '#states' => [
        'visible' => [
          'select[name="variationType"]' => ['value' => 'NEW'],
        ],
      ],
      '#tree' => TRUE,
      '#access' => $product_type->isNew(),
    ];
    $form['newVariationType']['markup'] = [
      '#markup' => $this->t('The label and id of the product variation type to be created will be the same as the ones provided above for the product type (if possible).'),
    ];
    $form['newVariationType']['generateTitle'] = [
      '#type' => 'checkbox',
      '#title' => t('Generate variation titles based on attribute values.'),
      '#default_value' => 0,
    ];
    if (\Drupal::moduleHandler()->moduleExists('commerce_order')) {
      // Prepare a list of line item types used to purchase product variations.
      $line_item_type_storage = $this->entityTypeManager->getStorage('commerce_line_item_type');
      $line_item_types = $line_item_type_storage->loadMultiple();
      $line_item_types = array_filter($line_item_types, function($line_item_type) {
        return $line_item_type->getPurchasableEntityTypeId() == 'commerce_product_variation';
      });
      $line_item_types = array_map(function ($line_item_type) {
        return $line_item_type->label();
      }, $line_item_types);

      $form['newVariationType']['lineItemType'] = [
        '#type' => 'select',
        '#title' => $this->t('Line item type'),
        '#default_value' => '',
        '#options' => $line_item_types,
        '#empty_value' => '',
        '#states' => [
          'required' => [
            'select[name="variationType"]' => ['value' => 'NEW'],
          ],
        ],
        '#element_validate' => [
          '::newVarationTypeRequiredValidate',
        ],
      ];
    }
    $form['product_status'] = [
      '#type' => 'checkbox',
      '#title' => t('Publish new products of this type by default.'),
      '#default_value' => $product->isPublished(),
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
          'bundle' => $product_type->id(),
        ],
        '#default_value' => ContentLanguageSettings::loadByEntityTypeBundle('commerce_product', $product_type->id()),
      ];
      $form['#submit'][] = 'language_configuration_element_submit';
    }

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Create a new product variation type.
    if ($form_state->getValue('variationType') == 'NEW') {
      $form_state->setValue(['newVariationType', 'label'], $form_state->getValue('label'));
      // Make the new product variation type id unique.
      $suffix = '';
      while (!empty(ProductVariationType::load($form_state->getValue('id') . $suffix))) {
        if ($suffix == '') {
          $suffix  = 0;
        }
        else {
          $suffix++;
        }
      }
      $form_state->setValue(['newVariationType', 'id'], $form_state->getValue('id') . $suffix);

      $product_variation_type = $this->entityManager->getStorage('commerce_product_variation_type')->create($form_state->getValue('newVariationType'));
      $product_variation_type->save();
      $this->entity->setVariationTypeId($product_variation_type->id());
    }
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

  /**
   * Validate callback for new variation type fields.
   */
  public function newVarationTypeRequiredValidate(array $element, FormStateInterface $form_state, array $form) {
    if ($form_state->getValue('variationType') == 'NEW' && empty($element['#value'])) {
      $form_state->setError($element, $this->t('The "@title" field is required.', ['@title' => $element['#title']]));
    }
  }

}
