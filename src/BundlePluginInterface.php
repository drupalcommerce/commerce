<?php

namespace Drupal\commerce;

@trigger_error('The ' . __NAMESPACE__ . '\BundlePluginInterface is deprecated. Instead, use \Drupal\entity\BundlePlugin\BundlePluginInterface', E_USER_DEPRECATED);

use Drupal\entity\BundlePlugin\BundlePluginInterface as BaseBundlePluginInterface;

/**
 * Interface for plugins which act as entity bundles.
 *
 * Note: This code has moved to Entity API, see the parent class.
 *
 * @deprecated in Commerce 2.0
 */
interface BundlePluginInterface extends BaseBundlePluginInterface {}
