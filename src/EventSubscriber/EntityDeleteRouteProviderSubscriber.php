<?php

/**
 * @file
 * Contains \Drupal\commerce\EventSubscriber\EntityDeleteRouteProviderSubscriber.
 */

namespace Drupal\commerce\EventSubscriber;

use Drupal\commerce\Plugin\Action\ActionDeriver;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Ensures that bulk deletion routes can be provided by entity types.
 */
class EntityDeleteRouteProviderSubscriber implements EventSubscriberInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new EntityRouteProviderSubscriber instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * Provides routes on route rebuild time.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function onDynamicRouteEvent(RouteBuildEvent $event) {
    $route_collection = $event->getRouteCollection();
    // Only build routes for entities defined by enabled modules.
    $routed_types = array_intersect_key(
      $this->entityManager->getDefinitions(),
      array_flip(ActionDeriver::getTypes())
    );

    /** @var \Drupal\Core\Entity\EntityType $entity_type */
    foreach ($routed_types as $id => $entity_type) {
      $link = $entity_type->getLinkTemplate('multiple-delete-form');
      if (empty($link)) {
        continue;
      }

      $route = new Route(
        $link,
        [
          '_form' => '\Drupal\commerce\Form\DeleteMultiple',
          'entity_type' => $id,
        ],
        ['_permission' => $entity_type->getAdminPermission()]
      );
      $route_name = 'entity.' . $id . '.multiple_delete_confirm';
      if (!$route_collection->get($route_name)) {
        $route_collection->add($route_name, $route);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC][] = ['onDynamicRouteEvent'];
    return $events;
  }

}
