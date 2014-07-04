<?php

/**
 * @file
 * Contains Drupal\commerce\Entity\CommerceStoreType.
 */

namespace Drupal\commerce\Entity;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\commerce\CommerceStoreTypeInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\system\Tests\Entity\EntityQueryTest;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Commerce Store Type entity type.
 *
 * @ConfigEntityType(
 *   id = "commerce_store_type",
 *   label = @Translation("Store type"),
 *   controllers = {
 *     "list_builder" = "Drupal\commerce\CommerceStoreTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce\Form\CommerceStoreTypeForm",
 *       "edit" = "Drupal\commerce\Form\CommerceStoreTypeForm",
 *       "delete" = "Drupal\commerce\Form\CommerceStoreTypeDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer commerce_store_type entities",
 *   config_prefix = "commerce_store_type",
 *   bundle_of = "commerce_store",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "commerce.store_type_edit",
 *     "delete-form" = "commerce.store_type_delete"
 *   }
 * )
 */
class CommerceStoreType extends ConfigEntityBundleBase implements CommerceStoreTypeInterface {

  /**
   * The store type machine name.
   *
   * @var string
   */
  public $id;

  /**
   * The store type UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The store type label.
   *
   * @var string
   */
  public $label;

  /**
   * A brief description of this store type.
   *
   * @var string
   */
  public $description;

  /**
   * How many stores exist with this type ?
   *
   * @return int
   */
  public function getStoreCount() {
    $instance_type = $this->getEntityType()->getBundleOf();
    $query = $this->entityManager()
      ->getListBuilder($instance_type)
      ->getStorage()
      ->getQuery();


    $count = $query
      ->condition('type', $this->id())
      ->count()
      ->execute();

    return $count;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function delete() {
    if ($this->getStoreCount() > 0) {
      throw new EntityStorageException(strtr("Store Type @type may not be deleted while stores exists for it.", array(
        '@type' => String::checkPlain($this->entityTypeId),
      )));
    }
    parent::delete();
  }
}
