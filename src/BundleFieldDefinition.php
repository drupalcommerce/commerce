<?php

namespace Drupal\commerce;

@trigger_error('The ' . __NAMESPACE__ . '\BundleFieldDefinition is deprecated. Instead, use \Drupal\entity\BundleFieldDefinition', E_USER_DEPRECATED);

use Drupal\entity\BundleFieldDefinition as EntityBundleFieldDefinition;

/**
 * Provides a field definition class for bundle fields.
 *
 * Note: This code has moved to Entity API, see the parent class.
 *
 * @deprecated in Commerce 2.0
 */
class BundleFieldDefinition extends EntityBundleFieldDefinition {}
