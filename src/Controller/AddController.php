<?php

/**
 * @file
 * Contains \Drupal\commerce\Controller\AddController.
 */

namespace Drupal\commerce\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * A generic controller for creating content entities.
 */
class AddController extends ControllerBase {

  /**
   * Displays add links for the available bundles.
   *
   * Redirects to the add form if there's only one bundle available.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   If there's only one available bundle, a redirect response.
   *   Otherwise, a render array with the add links for each bundle.
   */
  public function addPage(RouteMatchInterface $routeMatch) {
    $routeName = $routeMatch->getRouteName();
    $defaults = $routeMatch->getRouteObject()->getDefaults();
    if (empty($defaults['_bundle_type'])) {
      throw new \InvalidArgumentException(sprintf('The route "%s" must have a "_bundle_type" default parameter.', $routeName));
    }

    $formRouteName = str_replace('.add_page', '.add_form', $routeName);
    $bundleType = $defaults['_bundle_type'];
    $bundles = $this->entityManager()->getStorage($bundleType)->loadMultiple();
    // Filter out the bundles the user doesn't have access to.
    $accessControlHandler = $this->entityManager()->getAccessControlHandler($bundleType);
    $bundles = array_filter($bundles, function($bundle) use ($accessControlHandler) {
      return $accessControlHandler->createAccess($bundle->id());
    });
    // Redirect if there's only one bundle available.
    if (count($bundles) == 1) {
      $bundle = reset($bundles);

      return $this->redirect($formRouteName, [$bundleType => $bundle->id()]);
    }

    return [
      '#theme' => 'commerce_add_list',
      '#bundles' => $bundles,
      '#bundle_type' => $bundleType,
      '#form_route_name' => $formRouteName,
    ];
  }

  /**
   * Provides the add form for an entity of a specific bundle.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   *
   * @return array
   *   The add form.
   */
  public function addForm(RouteMatchInterface $routeMatch) {
    $bundle = $this->getBundleFromRouteMatch($routeMatch);
    $entityType = $bundle->getEntityType()->getBundleOf();
    $bundleKey = $this->entityManager()->getDefinition($entityType)->getKey('bundle');
    $entity = $this->entityManager()->getStorage($entityType)->create([
      $bundleKey => $bundle->id(),
    ]);

    return $this->entityFormBuilder()->getForm($entity, 'add');
  }

  /**
   * The title callback for the add form page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   *
   * @return string
   *   The page title.
   */
  public function addFormTitle(RouteMatchInterface $routeMatch) {
    $bundle = $this->getBundleFromRouteMatch($routeMatch);

    return $this->t('Create @label', ['@label' => $bundle->label()]);
  }

  /**
   * Gets the bundle object from the route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The bundle object as determined from the passed-in route match.
   */
  protected function getBundleFromRouteMatch(RouteMatchInterface $routeMatch) {
    // Assume that the bundle is the last route parameter.
    $parameters = $routeMatch->getParameters()->all();
    $bundle = end($parameters);

    return $bundle;
  }

}
