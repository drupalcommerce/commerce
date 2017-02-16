<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides the base payment method type class.
 */
abstract class PaymentMethodTypeBase extends PluginBase implements PaymentMethodTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreateLabel() {
    return $this->pluginDefinition['create_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

}
