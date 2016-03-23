<?php

namespace Drupal\commerce_checkout\Controller;

use Drupal\commerce_cart\CartSessionInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the checkout form page.
 */
class CheckoutController implements ContainerInjectionInterface {

  use DependencySerializationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The cart session.
   *
   * @var \Drupal\commerce_cart\CartSessionInterface
   */
  protected $cartSession;

  /**
   * Constructs a new CheckoutController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\commerce_cart\CartSessionInterface $cart_session
   *   The cart session.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder, CartSessionInterface $cart_session) {
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->cartSession = $cart_session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
      $container->get('commerce_cart.cart_session')
    );
  }

  /**
   * Builds and processes the form provided by the order's checkout flow.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   The render form.
   */
  public function formPage(RouteMatchInterface $route_match) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');
    $checkout_flow = $this->getCheckoutFlow($order);
    $form_state = new FormState();
    return $this->formBuilder->buildForm($checkout_flow->getPlugin(), $form_state);
  }

  /**
   * Checks access for the form page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');

    $access = AccessResult::allowedIfHasPermission($account, 'access checkout');
    // The user can checkout only their own non-empty orders.
    if ($account->isAuthenticated()) {
      $owner_check = $account->id() == $order->getOwnerId();
    }
    else {
      $owner_check = $this->cartSession->hasCartId($order->id());
    }
    $access
      ->andIf(AccessResult::allowedIf($owner_check))
      ->andIf(AccessResult::allowedIf($order->hasLineItems()))
      ->addCacheableDependency($order);

    return $access;
  }

  /**
   * Gets the order's checkout flow.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\commerce_checkout\Entity\CheckoutFlowInterface
   *   THe checkout flow.
   */
  protected function getCheckoutFlow(OrderInterface $order) {
    if ($order->checkout_flow->isEmpty()) {
      /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
      $order_type = $this->entityTypeManager->getStorage('commerce_order_type')->load($order->bundle());
      $checkout_flow = $order_type->getThirdPartySetting('commerce_checkout', 'checkout_flow');
      // @todo Allow other modules to add their own resolving logic.
      $order->checkout_flow->target_id = $checkout_flow;
      $order->save();
    }

    return $order->checkout_flow->entity;
  }


}
