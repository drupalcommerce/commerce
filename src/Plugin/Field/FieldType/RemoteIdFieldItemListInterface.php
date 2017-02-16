<?php

namespace Drupal\commerce\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Defines the interface for the 'commerce_remote_id' field item list class.
 */
interface RemoteIdFieldItemListInterface extends FieldItemListInterface {

  /**
   * Gets the remote ID for the given provider.
   *
   * @param string $provider
   *   The provider.
   *
   * @return string
   *   The remote ID if found, NULL otherwise.
   */
  public function getByProvider($provider);

  /**
   * Sets the remote ID for the given provider.
   *
   * @param string $provider
   *   The provider.
   * @param string $remote_id
   *   The remote ID.
   */
  public function setByProvider($provider, $remote_id);

}
