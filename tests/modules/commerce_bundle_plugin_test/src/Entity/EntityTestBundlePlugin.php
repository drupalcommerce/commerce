<?php

namespace Drupal\commerce_bundle_plugin_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * Defines the 'entity_test_bundle_plugin' entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_bundle_plugin",
 *   label = @Translation("Entity test bundle plugin"),
 *   bundle_label = @Translation("Bundle Plugin Test"),
 *   bundle_plugin_type = "bundle_plugin_test",
 *   base_table = "entity_test_bundle_plugin",
 *   admin_permission = "administer content",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "method_id",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   },
 * )
 */
class EntityTestBundlePlugin extends ContentEntityBase {

}
