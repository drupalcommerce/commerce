<?php

namespace Drupal\commerce_product\ConfigTranslation;

use Drupal\config_translation\ConfigEntityMapper;

/**
 * Provides a configuration mapper for product attributes.
 */
class ProductAttributeMapper extends ConfigEntityMapper {

  /**
   * {@inheritdoc}
   */
  public function getAddRoute() {
    $route = parent::getAddRoute();
    $route->setDefault('_form', '\Drupal\commerce_product\Form\ProductAttributeTranslationAddForm');
    return $route;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRoute() {
    $route = parent::getEditRoute();
    $route->setDefault('_form', '\Drupal\commerce_product\Form\ProductAttributeTranslationEditForm');
    return $route;
  }

}
