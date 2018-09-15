<?php

namespace Drupal\commerce_product;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler as CoreEntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an access control handler for product attribute values.
 *
 * Product attribute values are always managed in the scope of their parent
 * (the product attribute), so the parent access is used when possible:
 * - A product attribute value can be created, updated or deleted if the
 *   parent can be updated.
 * - A product attribute value can be viewed by any user with the
 *   "access content" permission, to allow rendering on any product.
 *   This matches the logic used by taxonomy terms.
 */
class ProductAttributeValueAccessControlHandler extends CoreEntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ProductAttributeValueAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($operation == 'view') {
      $result = AccessResult::allowedIfHasPermission($account, 'access content');
    }
    else {
      /** @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface $entity */
      $result = $entity->getAttribute()->access('update', $account, TRUE);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $product_attribute_storage = $this->entityTypeManager->getStorage('commerce_product_attribute');
    $product_attribute = $product_attribute_storage->create([
      'id' => $entity_bundle,
    ]);
    $result = $product_attribute->access('update', $account, TRUE);

    return $result;
  }

}
