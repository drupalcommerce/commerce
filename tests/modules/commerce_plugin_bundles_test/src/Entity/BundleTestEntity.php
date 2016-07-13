<?php

namespace Drupal\commerce_plugin_bundles_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * Defines the payment method entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_bundle_test_entity",
 *   label = @Translation("Bundles test entity"),
 *   bundle_label = @Translation("Bundles test entity type"),
 *   bundle_plugin_type = "commerce_plugin_bundles",
 *   handlers = {
 *     "storage" = "Drupal\commerce\CommerceContentEntityStorage",
 *   },
 *   base_table = "commerce_bundle_test_entity",
 *   admin_permission = "administer content",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "method_id",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   },
 * )
 */
class BundleTestEntity extends ContentEntityBase {

}
