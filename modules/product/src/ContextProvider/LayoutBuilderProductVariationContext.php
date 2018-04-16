<?php

namespace Drupal\commerce_product\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

class LayoutBuilderProductVariationContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new ProductRouteContext object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $context_definition = new ContextDefinition('entity:commerce_product_variation', new TranslatableMarkup('Product variation'));
    $value = NULL;

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    if ($product = $this->routeMatch->getParameter('commerce_product')) {
      $value = $product->getDefaultVariation();
    }
    elseif (strpos($this->routeMatch->getRouteName(), 'layout_builder') !== FALSE) {
      /** @var \Drupal\layout_builder\DefaultsSectionStorageInterface $section_storage */
      $section_storage = $this->routeMatch->getParameter('section_storage');
      $contexts = $section_storage->getContexts();
      /** @var \Drupal\commerce_product\Entity\ProductInterface $sample_product */
      $sample_product = $contexts['layout_builder.entity']->getContextValue();

      $sample_entity_generator = \Drupal::getContainer()->get('layout_builder.sample_entity_generator');
      $value = $sample_entity_generator->get('commerce_product_variation', $sample_product->bundle());
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
