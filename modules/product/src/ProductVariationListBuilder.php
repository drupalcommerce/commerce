<?php

namespace Drupal\commerce_product;

use Drupal\commerce\EntityHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder for product variations.
 */
class ProductVariationListBuilder extends EntityListBuilder implements FormInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The parent product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * The delta values of the variation field items.
   *
   * @var integer[]
   */
  protected $variationDeltas = [];

  /**
   * Whether tabledrag is enabled.
   *
   * @var bool
   */
  protected $hasTableDrag = TRUE;

  /**
   * Constructs a new ProductVariationListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityRepositoryInterface $entity_repository, RouteMatchInterface $route_match, FormBuilderInterface $form_builder) {
    parent::__construct($entity_type, $storage);

    $this->formBuilder = $form_builder;
    $this->product = $route_match->getParameter('commerce_product');
    $this->product = $entity_repository->getTranslationFromContext($this->product);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('entity.repository'),
      $container->get('current_route_match'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_product_variations';
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $variations = $this->product->getVariations();
    foreach ($variations as $delta => $variation) {
      $this->variationDeltas[$variation->id()] = $delta;
    }
    return $variations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['sku'] = $this->t('SKU');
    $header['title'] = $this->t('Title');
    $header['price'] = $this->t('Price');
    $header['status'] = $this->t('Status');
    if ($this->hasTableDrag) {
      $header['weight'] = $this->t('Weight');
    }
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $entity */
    if ($attribute_values = $entity->getAttributeValues()) {
      // The generated variation title includes the product title, which isn't
      // relevant in this context, the user only needs to see the attributes.
      $attribute_labels = EntityHelper::extractLabels($attribute_values);
      $title = implode(', ', $attribute_labels);
    }
    else {
      $title = $entity->label();
    }

    $row['#attributes']['class'][] = 'draggable';
    $row['#weight'] = $this->variationDeltas[$entity->id()];
    $row['sku'] = $entity->getSku();
    $row['title'] = $title;
    $row['price'] = $entity->getPrice();
    $row['status'] = $entity->isPublished() ? $this->t('Published') : $this->t('Unpublished');
    if ($this->hasTableDrag) {
      $row['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $entity->label()]),
        '#title_display' => 'invisible',
        '#default_value' => $this->variationDeltas[$entity->id()],
        '#attributes' => ['class' => ['weight']],
      ];
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = $this->formBuilder->getForm($this);
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = [
        '#type' => 'pager',
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $variations = $this->load();
    if (count($variations) <= 1) {
      $this->hasTableDrag = FALSE;
    }
    $delta = 10;
    // Dynamically expand the allowed delta based on the number of entities.
    $count = count($variations);
    if ($count > 20) {
      $delta = ceil($count / 2);
    }

    // Override the page title to contain the product label.
    $form['#title'] = $this->t('%product variations', ['%product' => $this->product->label()]);

    $form['variations'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => $this->t('There are no @label yet.', ['@label' => $this->entityType->getPluralLabel()]),
    ];
    foreach ($variations as $entity) {
      $row = $this->buildRow($entity);
      $row['sku'] = ['#markup' => $row['sku']];
      $row['title'] = ['#markup' => $row['title']];
      $row['price'] = [
        '#type' => 'inline_template',
        '#template' => '{{ price|commerce_price_format }}',
        '#context' => [
          'price' => $row['price'],
        ],
      ];
      $row['status'] = ['#markup' => $row['status']];
      if (isset($row['weight'])) {
        $row['weight']['#delta'] = $delta;
      }
      $form['variations'][$entity->id()] = $row;
    }

    if ($this->hasTableDrag) {
      $form['variations']['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'weight',
      ];
      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Save'),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $variations = $this->product->getVariations();
    $new_variations = [];
    foreach ($form_state->getValue('variations') as $id => $value) {
      $new_variations[$value['weight']] = $variations[$this->variationDeltas[$id]];
    }
    $this->product->setVariations($new_variations);
    $this->product->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->access('create') && $entity->hasLinkTemplate('duplicate-form')) {
      $operations['duplicate'] = [
        'title' => $this->t('Duplicate'),
        'weight' => 20,
        'url' => $this->ensureDestination($entity->toUrl('duplicate-form')),
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  protected function ensureDestination(Url $url) {
    return $url->mergeOptions(['query' => ['destination' => Url::fromRoute('<current>')->toString()]]);
  }

}
