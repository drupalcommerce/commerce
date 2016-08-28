<?php

namespace Drupal\commerce;

use Drupal\Core\Entity\EntityBundleListenerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionListenerInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;

class BundlePluginInstaller implements BundlePluginInstallerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity bundle listener.
   *
   * @var \Drupal\Core\Entity\EntityBundleListenerInterface
   */
  protected $entityBundleListener;

  /**
   * The field storage definition listener.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionListenerInterface
   */
  protected $fieldStorageDefinitionListener;

  /**
   * The field definition listener.
   *
   * @var \Drupal\Core\Field\FieldDefinitionListenerInterface
   */
  protected $fieldDefinitionListener;

  /**
   * Constructs a new BundlePluginInstaller object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityBundleListenerInterface $entity_bundle_listener
   *   The entity bundle listener.
   * @param \Drupal\Core\Field\FieldStorageDefinitionListenerInterface $field_storage_definition_listener
   *   The field storage definition listener.
   * @param \Drupal\Core\Field\FieldDefinitionListenerInterface $field_definition_listener
   *   The field definition listener.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityBundleListenerInterface $entity_bundle_listener, FieldStorageDefinitionListenerInterface $field_storage_definition_listener, FieldDefinitionListenerInterface $field_definition_listener) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityBundleListener = $entity_bundle_listener;
    $this->fieldStorageDefinitionListener = $field_storage_definition_listener;
    $this->fieldDefinitionListener = $field_definition_listener;
  }

  /**
   * {@inheritdoc}
   */
  public function installBundles(EntityTypeInterface $entity_type, array $modules) {
    $bundle_handler = $this->entityTypeManager->getHandler($entity_type->id(), 'bundle_plugin');
    $bundles = array_filter($bundle_handler->getBundleInfo(), function ($bundle_info) use ($modules) {
      return in_array($bundle_info['provider'], $modules);
    });
    foreach (array_keys($bundles) as $bundle) {
      $this->entityBundleListener->onBundleCreate($bundle, $entity_type->id());
      foreach ($bundle_handler->getFieldDefinitions($bundle) as $definition) {
        $this->fieldStorageDefinitionListener->onFieldStorageDefinitionCreate($definition);
        $this->fieldDefinitionListener->onFieldDefinitionCreate($definition);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function uninstallBundles(EntityTypeInterface $entity_type, array $modules) {
    $bundle_handler = $this->entityTypeManager->getHandler($entity_type->id(), 'bundle_plugin');
    $bundles = array_filter($bundle_handler->getBundleInfo(), function ($bundle_info) use ($modules) {
      return in_array($bundle_info['provider'], $modules);
    });
    foreach (array_keys($bundles) as $bundle) {
      $this->entityBundleListener->onBundleDelete($bundle, $entity_type->id());
      foreach ($bundle_handler->getFieldDefinitions($bundle) as $definition) {
        $this->fieldDefinitionListener->onFieldDefinitionDelete($definition);
        $this->fieldStorageDefinitionListener->onFieldStorageDefinitionDelete($definition);
      }
    }
  }

}
