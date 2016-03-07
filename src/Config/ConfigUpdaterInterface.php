<?php

namespace Drupal\commerce\Config;

/**
 * Performs configuration updates on behalf of an extension.
 *
 * Service to provide methods for extensions to import, revert, or delete
 * configuration once they have been installed. Intended to be run from
 * hook_post_update_NAME(), since this involves the modification of entities.
 *
 * @see hook_post_update_NAME()
 */
interface ConfigUpdaterInterface {

  /**
   * Imports configuration from extension storage to active storage.
   *
   * @param string[] $config_names
   *   An array of configuration names.
   *
   * @return \Drupal\commerce\Config\ConfigUpdateResult
   *   Object containing failed and succeeded configuration object update.
   */
  public function import(array $config_names);

  /**
   * Reverts configuration to the value from extension storage.
   *
   * @param string[] $config_names
   *   An array of configuration names.
   * @param bool $skip_modified
   *   Whether to skip modified configuration, defaults to TRUE. If this is
   *   set to false, it will revert active configuration that has been
   *   modified to the configuration values in the extension.
   *
   * @return \Drupal\commerce\Config\ConfigUpdateResult
   *   Object containing failed and succeeded configuration object update.
   */
  public function revert(array $config_names, $skip_modified = TRUE);

  /**
   * Deletes a configuration item.
   *
   * @param string[] $config_names
   *   An array of configuration names.
   *
   * @return \Drupal\commerce\Config\ConfigUpdateResult
   *   Object containing failed and succeeded configuration object update.
   */
  public function delete(array $config_names);

  /**
   * Loads the current active value of configuration.
   *
   * @param string $config_name
   *   The configuration item's full name.
   *
   * @return array
   *   The configuration value.
   */
  public function loadFromActive($config_name);

  /**
   * Loads the extension storage value of configuration.
   *
   * This is the value from a file in the config/install or config/optional
   * directory of a module, theme, or install profile.
   *
   * @param string $config_name
   *   The configuration item's full name.
   *
   * @return array|false
   *   The configuration value, or FALSE if it could not be located.
   */
  public function loadFromExtension($config_name);

  /**
   * Compares a config item's has been modified since its installation.
   *
   * @param string $config_name
   *   The configuration item's full name.
   *
   * @return bool
   *   Returns TRUE is modified, FALSE if original configuration.
   */
  public function isModified($config_name);

}
