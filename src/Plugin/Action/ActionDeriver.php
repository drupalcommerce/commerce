<?php

/**
 * @file
 * Contains \Drupal\commerce\Plugin\Action\ActionDeriver.
 */

namespace Drupal\commerce\Plugin\Action;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\Derivative\DeriverInterface;

/**
 * Class ActionDeriver is the deriver class for Commerce DeleteAction.
 */
class ActionDeriver extends DeriverBase implements DeriverInterface {
  /**
   * Return an array of the Commerce content entity type names.
   *
   * @TODO Replace by a constant array when PHP 5.6 or higher can be required.
   */
  public static function getTypes() {
    return [
      'commerce_line_item',
      'commerce_order',
      'commerce_product',
      'commerce_product_variation',
      'commerce_store',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (empty($this->derivatives)) {
      $definitions = [];
      foreach (static::getTypes() as $type) {
        $definition = $base_plugin_definition;
        $definition['type'] = $type;
        $definition['confirm_form_route_name'] = 'entity.' . $type . '.multiple_delete_confirm';
        $definitions[$type] = $definition;
      }
      $this->derivatives = $definitions;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
