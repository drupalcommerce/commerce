<?php

namespace Drupal\commerce;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for generating per-bundle CRUD permissions.
 */
class EntityPermissions implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityPermissions object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Builds a list of permissions for the participating entity types.
   *
   * @return array
   *   The permissions.
   */
  public function buildPermissions() {
    $permissions = [];
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if ($entity_type->hasHandlerClass('permission_provider')) {
        $permission_provider_class = $entity_type->getHandlerClass('permission_provider');
        $permission_provider = $this->entityTypeManager->createHandlerInstance($permission_provider_class, $entity_type);
        $permissions += $permission_provider->buildPermissions($entity_type);
      }
    }

    return $permissions;
  }

}
