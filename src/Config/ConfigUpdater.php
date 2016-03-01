<?php

namespace Drupal\commerce\Config;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
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
    $this->extensionConfigStorage = new ExtensionInstallStorage($active_config_storage, InstallStorage::CONFIG_INSTALL_DIRECTORY);
    $this->extensionOptionalConfigStorage = new ExtensionInstallStorage($active_config_storage, InstallStorage::CONFIG_OPTIONAL_DIRECTORY);
    $this->configFactory = $config_factory;

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type => $definition) {
      if ($definition->isSubclassOf('Drupal\Core\Config\Entity\ConfigEntityInterface')) {
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
      // Read the config from the file.
      $value = $this->loadFromExtension($config_name);
      if (!$value) {
        $failed[$config_name] = $this->t('@config did not exist in extension storage', ['@config' => $config_name]);
        continue;
      }

      if ($this->loadFromActive($config_name)) {
        $failed[$config_name] = $this->t('@config already exists, use revert to update', ['@config' => $config_name]);
        continue;
      }

      $type = $this->getConfigType($config_name);

      // Save it as a new config entity or simple config.
      if ($type == 'system.simple') {
        $this->configFactory->getEditable($config_name)->setData($value)->save();
      }
      else {
        /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $entity_storage */
        $entity_storage = $this->entityTypeManager->getStorage($type);
        $entity = $entity_storage->createFromStorageRecord($value);
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
      // Read the config from the file.
      $value = $this->loadFromExtension($config_name);
      if (!$value) {
        $failed[$config_name] = $this->t('@config did not exist in extension storage', ['@config' => $config_name]);
        continue;
      }

      // Check the configuration object's hash and see if it has been modified.
      if ($this->isModified($config_name) && $skip_modified) {
        $failed[$config_name] = $this->t('@config has been modified and was not reverted', ['@config' => $config_name]);
        continue;
      }

      $type = $this->getConfigType($config_name);
      if ($type == 'system.simple') {
        // Load the current config and replace the value.
        $this->configFactory->getEditable($config_name)->setData($value)->save();
      }
      else {
        // Load the current config entity and replace the value, with the
        // old UUID.
        $definition = $this->entityTypeManager->getDefinition($type);
        $id_key = $definition->getKey('id');

        $id = $value[$id_key];
        /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $entity_storage */
        $entity_storage = $this->entityTypeManager->getStorage($type);
        /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
        $entity = $entity_storage->load($id);
        $uuid = $entity->get('uuid');
        $entity = $entity_storage->updateFromStorageRecord($entity, $value);
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
      $value = $this->loadFromActive($config_name);
      if (!$value) {
        $failed[$config_name] = $this->t('@config did not exist in extension storage', ['@config' => $config_name]);
        continue;
      }

      // Check the configuration object's hash and see if it has been modified.
      if ($this->isModified($config_name)) {
        $failed[$config_name] = $this->t('@config has been modified and was not deleted', ['@config' => $config_name]);
        continue;
      }

      $type = $this->getConfigType($config_name);
      if ($type == 'system.simple') {
        $config = $this->configFactory->getEditable($config_name);
        if (!$config) {
          $failed[$config_name] = $this->t('@config did not exist in active storage', ['@config' => $config_name]);
          continue;
        }
        $config->delete();
      }
      else {
        $definition = $this->entityTypeManager->getDefinition($type);
        $id_key = $definition->getKey('id');
        $id = $value[$id_key];

        /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $entity_storage */
        $entity_storage = $this->entityTypeManager->getStorage($type);
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
    $value = $this->extensionConfigStorage->read($config_name);
    if (!$value) {
      $value = $this->extensionOptionalConfigStorage->read($config_name);
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function isModified($config_name) {
    $active = $this->activeConfigStorage->read($config_name);

    // Get the hash created when the config was installed.
    $original_hash = $active['_core']['default_config_hash'];

    // Remove export keys not used to generate default config hash.
    unset($active['uuid']);
    unset($active['_core']);
    $active_hash = Crypt::hashBase64(serialize($active));

    return $original_hash !== $active_hash;
  }

  /**
   * Gets the config type for a given config object.
   *
   * @param string $config_name
   *   Name of the config object.
   *
   * @return string
   *   Name of the config type.
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
