<?php

namespace Drupal\commerce_product;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder for product types.
 */
class ProductTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * The variation type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $variationTypeStorage;

  /**
   * Constructs a new ProductTypeListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type, $storage);

    $this->variationTypeStorage = $entity_type_manager->getStorage('commerce_product_variation_type');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Product type');
    $header['type'] = $this->t('Machine name');
    $header['product_variation_type'] = $this->t('Product variation type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $variation_type = $this->variationTypeStorage->load($entity->getVariationTypeId());
    $row['name'] = $entity->label();
    $row['type'] = $entity->id();
    if (empty($variation_type)) {
      $row['product_variation_type'] = $this->t('N/A');
    }
    else {
      $row['product_variation_type']['data'] = [
        '#type' => 'link',
        '#title' => $variation_type->label(),
        '#url' => $variation_type->toUrl('edit-form'),
      ];
    }
    return $row + parent::buildRow($entity);
  }

}
