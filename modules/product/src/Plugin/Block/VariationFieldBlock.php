<?php

namespace Drupal\commerce_product\Plugin\Block;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\ProductVariationFieldRendererInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\layout_builder\Plugin\Block\FieldBlock;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Variation field block.
 *
 * Specific block class for Layout Builder's field block and variations to
 * ensure field replacement works.
 */
class VariationFieldBlock extends FieldBlock {

  /**
   * The variation field renderer.
   *
   * @var \Drupal\commerce_product\ProductVariationFieldRendererInterface
   */
  protected $productVariationFieldRenderer;

  /**
   * Constructs a new VariationFieldBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_manager
   *   The formatter manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\commerce_product\ProductVariationFieldRendererInterface $product_variation_field_render
   *   The variation field renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entity_field_manager, FormatterPluginManager $formatter_manager, ModuleHandlerInterface $module_handler, LoggerInterface $logger, ProductVariationFieldRendererInterface $product_variation_field_render) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager, $formatter_manager, $module_handler, $logger);
    $this->productVariationFieldRenderer = $product_variation_field_render;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.formatter'),
      $container->get('module_handler'),
      $container->get('logger.channel.layout_builder'),
      $container->get('commerce_product.variation_field_renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $display_settings = $this->getConfiguration()['formatter'];
    $entity = $this->getEntity();
    assert($entity instanceof ProductVariationInterface);
    try {
      $build = $this->productVariationFieldRenderer->renderField($this->fieldName, $entity, $display_settings);
    }
    catch (\Exception $e) {
      $build = [];
      $this->logger->warning('The field "%field" failed to render with the error of "%error".', ['%field' => $this->fieldName, '%error' => $e->getMessage()]);
    }
    CacheableMetadata::createFromObject($this)->applyTo($build);
    return $build;
  }

}
