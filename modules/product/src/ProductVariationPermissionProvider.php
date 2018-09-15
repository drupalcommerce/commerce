<?php

namespace Drupal\commerce_product;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity\EntityPermissionProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides permissions for product variations.
 */
class ProductVariationPermissionProvider implements EntityPermissionProviderInterface, EntityHandlerInterface {

  use StringTranslationTrait;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new ProductVariationPermissionProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    $permissions = [];
    foreach ($bundles as $bundle_name => $bundle_info) {
      // The title is in a different format than the product type permissions,
      // to differentiate product types from product variation types.
      $permissions["manage {$bundle_name} {$entity_type_id}"] = [
        'title' => $this->t('[Product variations] Manage %bundle', [
          '%bundle' => $bundle_info['label'],
        ]),
        'provider' => 'commerce_product',
      ];
    }

    return $permissions;
  }

}
