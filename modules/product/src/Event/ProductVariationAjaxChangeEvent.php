<?php

namespace Drupal\commerce_product\Event;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the product variation ajax change event.
 *
 * @see \Drupal\commerce_product\Event\ProductEvents
 */
class ProductVariationAjaxChangeEvent extends Event {

  /**
   * The product variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $productVariation;

  /**
   * The ajax response.
   *
   * @var \Drupal\Core\Ajax\AjaxResponse
   */
  protected $response;

  /**
   * The view mode.
   *
   * @var string
   */
  protected $viewMode;

  /**
   * Constructs a new ProductVariationAjaxChangeEvent.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation
   *   The product variation.
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The ajax response.
   * @param string $view_mode
   *   The view mode used to render the product variation.
   */
  public function __construct(ProductVariationInterface $product_variation, AjaxResponse $response, $view_mode = 'default') {
    $this->productVariation = $product_variation;
    $this->response = $response;
    $this->viewMode = $view_mode;
  }

  /**
   * The product variation.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface
   *   The product variation.
   */
  public function getProductVariation() {
    return $this->productVariation;
  }

  /**
   * The ajax response.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax reponse.
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * The view mode used to render the product variation.
   *
   * @return string
   *   The view mode.
   */
  public function getViewMode() {
    return $this->viewMode;
  }

}
