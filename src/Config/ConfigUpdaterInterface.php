<?php

namespace Drupal\commerce\Config;

/**
 * Performs configuration updates.
 *
 * Allows an extension to import, revert, delete configuration.
 * Needs to be used from hook_post_update_NAME(), since it uses the entity API.
 *
 * @see hook_post_update_NAME()
 */
interface ConfigUpdaterInterface {

  /**
   * Imports configuration from extension storage to active storage.
   *
   * @param string[] $config_names
   *   The configuration names.
   *
   * @return \Drupal\commerce\Config\ConfigUpdateResult
   *   The result.
   */
  public function import(array $config_names);

  /**
   * Reverts configuration to the values from extension storage.
   *
   * @param string[] $config_names
   *   The configuration names.
   * @param bool $skip_modified
   *   Whether to skip modified configuration.
   *
   * @return \Drupal\commerce\Config\ConfigUpdateResult
   *   The result.
   */
  public function revert(array $config_names, $skip_modified = TRUE);

  /**
   * Deletes configuration.
   *
   * @param string[] $config_names
   *   The configuration names.
   *
   * @return \Drupal\commerce\Config\ConfigUpdateResult
   *   The result.
   */
  public function delete(array $config_names);

  /**
   * Loads configuration from active storage.
   *
   * @param string $config_name
   *   The configuration name.
   *
   * @return array|false
   *   The configuration data, or FALSE if not found.
   */
  public function loadFromActive($config_name);

  /**
   * Loads configuration from extension storage.
   *
   * Extension storage represents the config/install or config/optional
   * directory of a module, theme, or install profile.
   *
   * @param string $config_name
   *   The configuration name.
   *
   * @return array|false
   *   The configuration data, or FALSE if not found.
   */
  public function loadFromExtension($config_name);

  /**
   * Checks whether the configuration was modified since the initial import.
   *
   * @param array $config
   *   The configuration data.
   *
   * @return bool
   *   TRUE if the configuration was modified, FALSE otherwise.
   */
  public function isModified(array $config);

}
