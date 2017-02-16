<?php

namespace Drupal\commerce;

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Provides a field definition class for bundle fields.
 *
 * Core currently doesn't provide one, the hook_entity_bundle_field_info()
 * example uses BaseFieldDefinition, which is wrong. Tracked in #2346347.
 *
 * Note that this class implements both FieldStorageDefinitionInterface and
 * FieldStorageDefinitionInterface. This is a Commerce simplification for
 * DX reasons, allowing code to return just the bundle definitions instead of
 * having to return both storage definitions and bundle definitions.
 */
class BundleFieldDefinition extends BaseFieldDefinition {

  /**
   * {@inheritdoc}
   */
  public function isBaseField() {
    return FALSE;
  }

}
