<?php

namespace Drupal\commerce;

@trigger_error('The ' . __NAMESPACE__ . '\EntityAccessControlHandler is deprecated. Instead, use \Drupal\entity\EntityAccessControlHandler', E_USER_DEPRECATED);

use Drupal\entity\EntityAccessControlHandler as BaseEntityAccessControlHandler;

/**
 * Controls access based on the Commerce entity permissions.
 *
 * Note: This code has moved to Entity API, see the parent class.
 *
 * @deprecated in Commerce 2.0
 */
class EntityAccessControlHandler extends BaseEntityAccessControlHandler {}
