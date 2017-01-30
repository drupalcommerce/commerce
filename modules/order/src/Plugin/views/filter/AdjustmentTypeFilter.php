<?php

namespace Drupal\commerce_order\Plugin\views\filter;

use Drupal\commerce_order\AdjustmentTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides filtering by adjustment type.
 *
 * @ViewsFilter("commerce_adjustment_type_filter")
 */
class AdjustmentTypeFilter extends InOperator implements ContainerFactoryPluginInterface {

  /**
   * The adjustment type manager.
   *
   * @var \Drupal\commerce_order\AdjustmentTypeManager
   */
  protected $adjustmentTypeManager;

  /**
   * Constructs a new AdjustmentTypeFilter instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_order\AdjustmentTypeManager $adjustment_type_manager
   *   The adjustment type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AdjustmentTypeManager $adjustment_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->adjustmentTypeManager = $adjustment_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.commerce_adjustment_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    $definitions = $this->adjustmentTypeManager->getDefinitions();
    if (!isset($this->valueOptions)) {
      $this->valueTitle = $this->t('Adjustment types:');
      foreach ($definitions as $type) {
        $this->valueOptions[$type['id']] = $type['label'];
      }
    }
    return $this->valueOptions;
  }

}
