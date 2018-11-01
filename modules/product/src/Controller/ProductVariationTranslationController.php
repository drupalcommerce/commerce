<?php

namespace Drupal\commerce_product\Controller;

use Drupal\content_translation\Controller\ContentTranslationController;
use Drupal\Core\Routing\RouteMatchInterface;

class ProductVariationTranslationController extends ContentTranslationController {

  /**
   * {@inheritdoc}
   *
   * Workaround for #3004037.
   * Core does not generate URLs via the given entity, causing the required
   * 'commerce_product' parameter to be missing for every variation URL.
   */
  public function overview(RouteMatchInterface $route_match, $entity_type_id = NULL) {
    $build = parent::overview($route_match, $entity_type_id);
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $entity */
    $entity = $build['#entity'];
    foreach ($build['content_translation_overview']['#rows'] as &$row) {
      foreach ($row as &$column) {
        if (!is_array($column) || empty($column['data']['#type']) || $column['data']['#type'] != 'operations') {
          continue;
        }
        foreach ($column['data']['#links'] as &$link) {
          /** @var \Drupal\Core\Url $url */
          $url = $link['url'];
          $url->setRouteParameter('commerce_product', $entity->getProductId());
        }
      }
    }
    return $build;
  }

}
