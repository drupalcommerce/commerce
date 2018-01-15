<?php

namespace Drupal\commerce_product\ContextProvider;

use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current product as context on commerce_product routes.
 *
 * @todo Remove once core gets a generic EntityRouteContext.
 */
class ProductRouteContext implements ContextProviderInterface {

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
    $context_definition = new ContextDefinition('entity:commerce_product', NULL, FALSE);
    $value = NULL;
    if ($product = $this->routeMatch->getParameter('commerce_product')) {
      $value = $product;
    }
    elseif ($this->routeMatch->getRouteName() == 'entity.commerce_product.add_form') {
      $product_type = $this->routeMatch->getParameter('commerce_product_type');
      $value = Product::create(['type' => $product_type->id()]);
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);
    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);

    return ['commerce_product' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition(
      'entity:commerce_product', $this->t('Product from URL')
    ));
    return ['commerce_product' => $context];
  }

}
