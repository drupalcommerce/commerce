<?php

namespace Drupal\commerce_product\ContextProvider;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\layout_builder\DefaultsSectionStorageInterface;
use Drupal\layout_builder\OverridesSectionStorageInterface;

/**
 * @todo
 */
class ProductVariationContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The product variation storage.
   *
   * @var \Drupal\commerce_product\ProductVariationStorageInterface
   */
  protected $productVariationStorage;

  /**
   * Constructs a new ProductRouteContext object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->routeMatch = $route_match;
    $this->productVariationStorage = $entity_type_manager->getStorage('commerce_product_variation');
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $context_definition = new EntityContextDefinition('entity:commerce_product_variation', new TranslatableMarkup('Product variation'));
    $value = $this->routeMatch->getParameter('commerce_product_variation');
    if ($value === NULL) {
      if ($product = $this->routeMatch->getParameter('commerce_product')) {
        $value = $this->productVariationStorage->loadFromContext($product);
      }
      /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
      elseif ($product_type = $this->routeMatch->getParameter('commerce_product_type')) {
        if (is_string($product_type)) {
          $product_type = ProductType::load($product_type);
        }
        $value = $this->productVariationStorage->createWithSampleValues($product_type->getVariationTypeId());
      }
      // @todo Simplify this logic once EntityTargetInterface is available
      // @see https://www.drupal.org/project/drupal/issues/3054490
      elseif (strpos($this->routeMatch->getRouteName(), 'layout_builder') !== FALSE) {
        /** @var \Drupal\layout_builder\SectionStorageInterface $section_storage */
        $section_storage = $this->routeMatch->getParameter('section_storage');
        if ($section_storage instanceof DefaultsSectionStorageInterface) {
          $context = $section_storage->getContextValue('display');
          assert($context instanceof EntityDisplayInterface);
          if ($context->getTargetEntityTypeId() === 'commerce_product') {
            $product_type = ProductType::load($context->getTargetBundle());
            $value = $this->productVariationStorage->createWithSampleValues($product_type->getVariationTypeId());
          }
        }
        elseif ($section_storage instanceof OverridesSectionStorageInterface) {
          $context = $section_storage->getContextValue('entity');
          if ($context instanceof ProductInterface) {
            $value = $context->getDefaultVariation();
            if ($value === NULL) {
              $product_type = ProductType::load($context->bundle());
              $value = $this->productVariationStorage->createWithSampleValues($product_type->getVariationTypeId());
            }
          }
        }
      }
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);
    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);

    return ['commerce_product_variation' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return $this->getRuntimeContexts([]);
  }

}
