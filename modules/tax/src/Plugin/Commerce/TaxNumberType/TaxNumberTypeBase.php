<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxNumberType;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base class for tax number types.
 */
abstract class TaxNumberTypeBase extends PluginBase implements ContainerFactoryPluginInterface, TaxNumberTypeInterface {

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new TaxNumberTypeBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('datetime.time')
    );
  }

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
