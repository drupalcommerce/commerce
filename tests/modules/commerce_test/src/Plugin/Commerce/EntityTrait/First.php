<?php

namespace Drupal\commerce_test\Plugin\Commerce\EntityTrait;

use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;

/**
 * Provides the first entity trait.
 *
 * @CommerceEntityTrait(
 *   id = "first",
 *   label = @Translation("First"),
 *   entity_types = {"commerce_store"}
 * )
 */
class First extends EntityTraitBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['phone'] = BundleFieldDefinition::create('telephone')
      ->setLabel(t('Phone'))
      ->setRequired(TRUE);

    return $fields;
  }

}
