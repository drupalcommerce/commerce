<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentType;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides the base payment type class.
 */
abstract class PaymentTypeBase extends PluginBase implements PaymentTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowId() {
    return $this->pluginDefinition['workflow'];
  }

}
