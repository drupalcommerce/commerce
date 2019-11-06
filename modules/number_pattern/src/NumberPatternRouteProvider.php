<?php

namespace Drupal\commerce_number_pattern;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\Routing\DefaultHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Defines the route provider for number patterns.
 */
class NumberPatternRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    if ($reset_sequence_form_route = $this->getResetSequenceFormRoute($entity_type)) {
      $collection->add("entity.commerce_number_pattern.reset_sequence_form", $reset_sequence_form_route);
    }

    return $collection;
  }

  /**
   * Gets the reset-sequence-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getResetSequenceFormRoute(EntityTypeInterface $entity_type) {
    $route = new Route($entity_type->getLinkTemplate('reset-sequence-form'));
    $route
      ->addDefaults([
        '_entity_form' => 'commerce_number_pattern.reset-sequence',
        '_title' => 'Reset sequence',
      ])
      ->setRequirement('_entity_access', 'commerce_number_pattern.reset_sequence')
      ->setOption('parameters', [
        'commerce_number_pattern' => [
          'type' => 'entity:commerce_number_pattern',
        ],
      ]);

    return $route;
  }

}
