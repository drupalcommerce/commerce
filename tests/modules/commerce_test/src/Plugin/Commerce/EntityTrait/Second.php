<?php

namespace Drupal\commerce_test\Plugin\Commerce\EntityTrait;

use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;

/**
 * Provides the second entity trait.
 *
 * @CommerceEntityTrait(
 *   id = "second",
 *   label = @Translation("Second"),
 *   entity_types = {"commerce_store"}
 * )
 */
class Second extends EntityTraitBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    // Intentionally conflicts with the field in the first trait.
    $fields['phone'] = BundleFieldDefinition::create('telephone')
      ->setLabel(t('Phone'))
      ->setRequired(TRUE);

    return $fields;
  }

}
