<?php

namespace Drupal\commerce\Plugin\Field\FieldType;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Deriver for the executable plugin item field type.
 */
class PluginItemDeriver extends DeriverBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $supported = [
      'condition' => $this->t('Conditions'),
      'action' => $this->t('Action'),
      'commerce_promotion_offer' => $this->t('Promotion offer'),
      'commerce_promotion_condition' => $this->t('Promotion condition'),
    ];

    foreach ($supported as $id => $label) {
      $this->derivatives[$id] = [
        'plugin_type' => $id,
        'label' => $label,
        'category' => $this->t('Plugin'),
      ] + $base_plugin_definition;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
