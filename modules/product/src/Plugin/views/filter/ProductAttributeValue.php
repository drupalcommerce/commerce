<?php

namespace Drupal\commerce_product\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter for product attribute values.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("commerce_product_attribute_value")
 */
class ProductAttributeValue extends InOperator {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ProductAttributeValue object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $attribute_storage = $this->entityTypeManager->getStorage('commerce_product_attribute');
      /** @var \Drupal\commerce_product\Entity\ProductAttributeInterface $attribute */
      $attribute = $attribute_storage->load($this->definition['attribute']);
      foreach ($attribute->getValues() as $value) {
        $this->valueOptions[$value->id()] = $value->label();
      }
    }

    return $this->valueOptions;
  }

}
