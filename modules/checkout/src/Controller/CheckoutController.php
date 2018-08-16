<?php

namespace Drupal\commerce_checkout\Controller;

use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart\CartSession;
use Drupal\commerce_cart\CartSessionInterface;
use Drupal\commerce_checkout\CheckoutOrderManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides the checkout form page.
 */
class CheckoutController implements ContainerInjectionInterface {

  use DependencySerializationTrait;
  use UrlGeneratorTrait;

  /**
   * The checkout order manager.
   *
   * @var \Drupal\commerce_checkout\CheckoutOrderManagerInterface
   */
  protected $checkoutOrderManager;

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
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * Constructs a new CheckoutController object.
   *
   * @param \Drupal\commerce_checkout\CheckoutOrderManagerInterface $checkout_order_manager
   *   The checkout order manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\commerce_cart\CartSessionInterface $cart_session
   *   The cart session.
   */
  public function __construct(CheckoutOrderManagerInterface $checkout_order_manager, FormBuilderInterface $form_builder, CartSessionInterface $cart_session, CartProviderInterface $cart_provider) {
    $this->checkoutOrderManager = $checkout_order_manager;
    $this->formBuilder = $form_builder;
    $this->cartSession = $cart_session;
    $this->cartProvider = $cart_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_checkout.checkout_order_manager'),
      $container->get('form_builder'),
      $container->get('commerce_cart.cart_session'),
      $container->get('commerce_cart.cart_provider')
    );
  }

  /**
   * Convenience method to send user to checkout via a parameter-free route.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The checkout page.
   */
  public function checkoutRedirect() {
    $carts = $this->cartProvider->getCarts();
    $carts = array_filter($carts, function ($cart) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
      return $cart->hasItems();
    });
    $cart = current($carts);
    return $this->redirect('commerce_checkout.form', ['commerce_order' => $cart->id()]);
  }

  /**
   * Builds and processes the form provided by the order's checkout flow.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The render form.
   */
  public function formPage(RouteMatchInterface $route_match) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');
    $requested_step_id = $route_match->getParameter('step');
    $step_id = $this->checkoutOrderManager->getCheckoutStepId($order, $requested_step_id);
    if ($requested_step_id != $step_id) {
      return $this->redirect('commerce_checkout.form', ['commerce_order' => $order->id(), 'step' => $step_id]);
    }
    $checkout_flow = $this->checkoutOrderManager->getCheckoutFlow($order);
    $checkout_flow_plugin = $checkout_flow->getPlugin();

    return $this->formBuilder->getForm($checkout_flow_plugin, $step_id);
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
    if ($order->getState()->value == 'canceled') {
      return AccessResult::forbidden()->addCacheableDependency($order);
    }

    // The user can checkout only their own non-empty orders.
    if ($account->isAuthenticated()) {
      $customer_check = $account->id() == $order->getCustomerId();
    }
    else {
      $active_cart = $this->cartSession->hasCartId($order->id(), CartSession::ACTIVE);
      $completed_cart = $this->cartSession->hasCartId($order->id(), CartSession::COMPLETED);
      $customer_check = $active_cart || $completed_cart;
    }

    $access = AccessResult::allowedIf($customer_check)
      ->andIf(AccessResult::allowedIf($order->hasItems()))
      ->andIf(AccessResult::allowedIfHasPermission($account, 'access checkout'))
      ->addCacheableDependency($order);

    return $access;
  }

}
