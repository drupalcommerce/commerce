<?php

namespace Drupal\commerce\TwigExtension;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides the Commerce Twig extensions.
 */
class CommerceTwigExtension extends \Twig_Extension {

  /**
   * @inheritdoc
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('commerce_entity_render', [$this, 'renderEntity']),
    ];
  }

  /**
   * @inheritdoc
   */
  public function getName() {
    return 'commerce.twig_extension';
  }

  /**
   * Renders an entity in the given view mode.
   *
   * Example: {{ order_item.getPurchasableEntity|commerce_entity_render }}
   *
   * @param mixed $entity
   *   The entity.
   * @param string $view_mode
   *   The view mode.
   *
   * @return array
   *   A renderable array for the rendered entity.
   *
   * @throws \InvalidArgumentException
   */
  public static function renderEntity($entity, $view_mode = 'default') {
    if (empty($entity)) {
      // Nothing to render.
      return [];
    }
    if (!($entity instanceof ContentEntityInterface)) {
      throw new \InvalidArgumentException('The "commerce_entity_render" filter must be given a content entity.');
    }

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId());
    return $view_builder->view($entity, $view_mode);
  }

}
