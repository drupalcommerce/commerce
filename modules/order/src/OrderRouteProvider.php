<?php

namespace Drupal\commerce_order;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for the Order entity.
 */
class OrderRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type) {
    $route = parent::getCanonicalRoute($entity_type);
    // Replace the 'full' view mode with the 'admin' view mode.
    $route->setDefault('_entity_view', 'commerce_order.admin');

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    if ($resend_receipt_form_route = $this->getResendReceiptFormRoute($entity_type)) {
      $collection->add("entity.commerce_order.resend_receipt_form", $resend_receipt_form_route);
    }

    return $collection;
  }

  /**
   * Gets the resend-receipt-form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getResendReceiptFormRoute(EntityTypeInterface $entity_type) {
    $route = new Route($entity_type->getLinkTemplate('resend-receipt-form'));
    $route
      ->addDefaults([
        '_entity_form' => 'commerce_order.resend-receipt',
        '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
      ])
      ->setRequirement('_entity_access', 'commerce_order.resend_receipt')
      ->setRequirement('commerce_order', '\d+')
      ->setOption('parameters', [
        'commerce_order' => [
          'type' => 'entity:commerce_order',
        ],
      ])
      ->setOption('_admin_route', TRUE);

    return $route;
  }

}
