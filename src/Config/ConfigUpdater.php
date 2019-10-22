<?php

namespace Drupal\commerce\Config;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Default implementation of the ConfigUpdaterInterface.
 */
class ConfigUpdater implements ConfigUpdaterInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The active config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeConfigStorage;

  /**
   * The extension config storage for config/install config items.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $extensionConfigStorage;

  /**
   * The extension config storage for config/optional config items.
   *
   * @var \Drupal\Core\Config\ExtensionInstallStorage
   */
  protected $extensionOptionalConfigStorage;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * List of current config entity types, keyed by prefix.
   *
   * @var string[]
   */
  protected $typesByPrefix = [];

  /**
   * Constructs a new ConfigUpdater object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\StorageInterface $active_config_storage
   *   The active config storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StorageInterface $active_config_storage, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->activeConfigStorage = $active_config_storage;
    $this->extensionConfigStorage = new ExtensionInstallStorage($active_config_storage, InstallStorage::CONFIG_INSTALL_DIRECTORY, StorageInterface::DEFAULT_COLLECTION, TRUE, \Drupal::installProfile());
    $this->extensionOptionalConfigStorage = new ExtensionInstallStorage($active_config_storage, InstallStorage::CONFIG_OPTIONAL_DIRECTORY, StorageInterface::DEFAULT_COLLECTION, TRUE, \Drupal::installProfile());
    $this->configFactory = $config_factory;

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type => $definition) {
      if ($definition->entityClassImplements(ConfigEntityInterface::class)) {
        /** @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $definition */
        $prefix = $definition->getConfigPrefix();
        $this->typesByPrefix[$prefix] = $entity_type;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function import(array $config_names) {
    $succeeded = [];
    $failed = [];
    foreach ($config_names as $config_name) {
      $config_data = $this->loadFromExtension($config_name);
      if (!$config_data) {
        $failed[$config_name] = $this->t('@config does not exist in extension storage', ['@config' => $config_name]);
        continue;
      }
      if ($this->loadFromActive($config_name)) {
        $failed[$config_name] = $this->t('@config already exists, use revert to update', ['@config' => $config_name]);
        continue;
      }

      $config_type = $this->getConfigType($config_name);
      if ($config_type == 'system.simple') {
        $this->configFactory->getEditable($config_name)->setData($config_data)->save();
      }
      else {
        /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $entity_storage */
        $entity_storage = $this->entityTypeManager->getStorage($config_type);
        $entity = $entity_storage->createFromStorageRecord($config_data);
        $entity->save();
      }

      $succeeded[$config_name] = $this->t('@config was successfully imported', ['@config' => $config_name]);
    }

    return new ConfigUpdateResult($succeeded, $failed);
  }

  /**
   * {@inheritdoc}
   */
  public function revert(array $config_names, $skip_modified = TRUE) {
    $succeeded = [];
    $failed = [];
    foreach ($config_names as $config_name) {
      $config_data = $this->loadFromExtension($config_name);
      if (!$config_data) {
        $failed[$config_name] = $this->t('@config does not exist in extension storage', ['@config' => $config_name]);
        continue;
      }
      $active_config_data = $this->loadFromActive($config_name);
      if (!$active_config_data) {
        $succeeded[$config_name] = $this->t('Skipped: @config does not exist in active storage', ['@config' => $config_name]);
        continue;
      }
      if ($this->isModified($active_config_data) && $skip_modified) {
        $succeeded[$config_name] = $this->t('Skipped: @config was not reverted because it was modified by the user', ['@config' => $config_name]);
        continue;
      }

      $config_type = $this->getConfigType($config_name);
      if ($config_type == 'system.simple') {
        $this->configFactory->getEditable($config_name)->setData($config_data)->save();
      }
      else {
        /** @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_type */
        $entity_type = $this->entityTypeManager->getDefinition($config_type);
        $id = substr($config_name, strlen($entity_type->getConfigPrefix()) + 1);
        /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $entity_storage */
        $entity_storage = $this->entityTypeManager->getStorage($config_type);
        /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
        $entity = $entity_storage->load($id);
        // The UUID must remain unchanged between updates.
        $uuid = $entity->uuid();
        $entity = $entity_storage->updateFromStorageRecord($entity, $config_data);
        $entity->set('uuid', $uuid);
        $entity->save();
      }

      $succeeded[$config_name] = $this->t('@config was successfully reverted', ['@config' => $config_name]);
    }

    return new ConfigUpdateResult($succeeded, $failed);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $config_names) {
    $succeeded = [];
    $failed = [];
    foreach ($config_names as $config_name) {
      if (!$this->loadFromActive($config_name)) {
        $succeeded[$config_name] = $this->t('Skipped: @config does not exist in active storage', ['@config' => $config_name]);
        continue;
      }

      $config_type = $this->getConfigType($config_name);
      if ($config_type == 'system.simple') {
        $this->configFactory->getEditable($config_name)->delete();
      }
      else {
        /** @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_type */
        $entity_type = $this->entityTypeManager->getDefinition($config_type);
        $id = substr($config_name, strlen($entity_type->getConfigPrefix()) + 1);
        /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $entity_storage */
        $entity_storage = $this->entityTypeManager->getStorage($config_type);
        $entity = $entity_storage->load($id);
        $entity_storage->delete([$entity]);
      }

      $succeeded[$config_name] = $this->t('@config was successfully deleted', ['@config' => $config_name]);
    }

    return new ConfigUpdateResult($succeeded, $failed);
  }

  /**
   * {@inheritdoc}
   */
  public function loadFromActive($config_name) {
    return $this->activeConfigStorage->read($config_name);
  }

  /**
   * {@inheritdoc}
   */
  public function loadFromExtension($config_name) {
    $data = $this->extensionConfigStorage->read($config_name);
    if (!$data) {
      $data = $this->extensionOptionalConfigStorage->read($config_name);
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function isModified(array $config) {
    $original_hash = $config['_core']['default_config_hash'];
    // Create a new hash based on current values.
    unset($config['uuid']);
    unset($config['_core']);
    $current_hash = Crypt::hashBase64(serialize($config));

    return $original_hash !== $current_hash;
  }

  /**
   * Gets the config type for a given config object.
   *
   * @param string $config_name
   *   Name of the config object.
   *
   * @return string
   *   Name of the config type. Either 'system.simple' or an entity type ID.
   */
  protected function getConfigType($config_name) {
    foreach ($this->typesByPrefix as $prefix => $config_type) {
      if (strpos($config_name, $prefix) === 0) {
        return $config_type;
      }
    }

    return NULL;
  }

}
