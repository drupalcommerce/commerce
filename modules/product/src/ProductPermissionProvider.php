<?php

namespace Drupal\commerce_product;

use Drupal\commerce\EntityPermissionProvider;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides Product entity permissions.
 */
class ProductPermissionProvider extends EntityPermissionProvider {

  /**
   * {@inheritdoc}
   */
  public function buildPermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildPermissions($entity_type);

    $permissions["view published {$entity_type->id()}"] = [
      'title' => $this->t('View published @type', [
        '@type' => $entity_type->getSingularLabel(),
      ]),
    ];

    return $permissions;
  }

}
