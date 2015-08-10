<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Form\ProductTypeForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductTypeForm extends EntityForm {

  /**
   * The variation type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $variationTypeStorage;

  /**
   * Creates a new ProductTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entityManager) {
    $this->variationTypeStorage = $entityManager->getStorage('commerce_product_variation_type');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'));
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
      '#disabled' => !$productType->isNew(),
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
    $form['digital'] = [
      '#type' => 'checkbox',
      '#title' => t('Digital'),
      '#default_value' => $productType->isDigital(),
      '#description' => t('Products of this type represent digital services.')
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();
    drupal_set_message($this->t('The product type %label has been successfully saved.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_product_type.collection');

    if ($status == SAVED_NEW) {
      commerce_product_add_stores_field($this->entity);
      commerce_product_add_body_field($this->entity);
      commerce_product_add_variations_field($this->entity);
    }
  }

}
