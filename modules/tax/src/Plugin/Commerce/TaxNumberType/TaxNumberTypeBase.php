<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxNumberType;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides the base class for tax number types.
 */
abstract class TaxNumberTypeBase extends PluginBase implements TaxNumberTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCountries() {
    return $this->pluginDefinition['countries'];
  }

  /**
   * {@inheritdoc}
   */
  public function getExamples() {
    return $this->pluginDefinition['examples'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedExamples() {
    $formatted_examples = '';
    if ($this->pluginDefinition['examples']) {
      $count = count($this->pluginDefinition['examples']);
      $formatted_examples = $this->formatPlural($count, 'Example: @data.', 'Examples: @data.', [
        '@data' => implode(', ', $this->pluginDefinition['examples']),
      ]);
      $formatted_examples = (string) $formatted_examples;
    }

    return $formatted_examples;
  }

  /**
   * {@inheritdoc}
   */
  public function canonicalize($tax_number) {
    // Remove spaces, dots, dashes from the entered number.
    return preg_replace('/[ .-]/', '', $tax_number);
  }

}
