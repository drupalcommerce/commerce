<?php

namespace Drupal\commerce_plugin_bundles_test\Entity;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * Defines the 'entity_test_plugin_bundle' entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_plugin_bundle",
 *   label = @Translation("Entity test plugin bundle"),
 *   bundle_label = @Translation("Plugin bundle"),
 *   bundle_plugin_type = "commerce_plugin_bundle",
 *   base_table = "entity_test_plugin_bundle",
 *   admin_permission = "administer content",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "method_id",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   },
 * )
 */
class EntityTestPluginBundle extends ContentEntityBase {

}
