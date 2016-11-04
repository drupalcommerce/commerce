<?php

namespace Drupal\commerce_checkout\Controller;

use Drupal\commerce_cart\CartSession;
use Drupal\commerce_cart\CartSessionInterface;
use Drupal\commerce_checkout\CheckoutOrderManagerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides the checkout form page.
 */
class CheckoutController implements ContainerInjectionInterface {

  use DependencySerializationTrait;

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
   * Constructs a new CheckoutController object.
   *
   * @param \Drupal\commerce_checkout\CheckoutOrderManagerInterface $checkout_order_manager
   *   The checkout order manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\commerce_cart\CartSessionInterface $cart_session
   *   The cart session.
   */
  public function __construct(CheckoutOrderManagerInterface $checkout_order_manager, FormBuilderInterface $form_builder, CartSessionInterface $cart_session) {
    $this->checkoutOrderManager = $checkout_order_manager;
    $this->formBuilder = $form_builder;
    $this->cartSession = $cart_session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_checkout.checkout_order_manager'),
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
    $checkout_flow = $this->checkoutOrderManager->getCheckoutFlow($order);
    // The user is attempting to access an inaccessible page for their order.
    if (!$this->checkoutPageAccess($order)) {
      // Redirect if the target page is different from the page the user was
      // trying to access.
      $order_step = $this->getOrderCheckoutStep($order);
      if ($checkout_flow->getPlugin()->getStepId() !== $order_step) {
        $url = Url::fromRoute('commerce_checkout.form', ['commerce_order' => $order->id(), 'step' => $order_step]);
        return new RedirectResponse($url->toString());
      }
    }
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

    // The user can checkout only their own non-empty orders.
    if ($account->isAuthenticated()) {
      $customer_check = $account->id() == $order->getCustomerId();
    }
    else {
      $active_cart = $this->cartSession->hasCartId($order->id(), CartSession::ACTIVE);
      $completed_cart = $this->cartSession->hasCartId($order->id(), CartSession::COMPLETED);
      $customer_check = $active_cart || $completed_cart;
    }

    // The user is attempting to access an inaccessible page for their order.
    if (!$this->checkoutPageAccess($order)) {
      // Return a 403 response if the target page is the same from the page the
      // user was trying to access. We redirect in formPage() otherwise.
      $checkout_flow = $this->checkoutOrderManager->getCheckoutFlow($order);
      if ($checkout_flow->getPlugin()->getStepId() === $this->getOrderCheckoutStep($order)) {
        return AccessResult::forbidden();
      }
    }

    $access = AccessResult::allowedIf($customer_check)
      ->andIf(AccessResult::allowedIf($order->hasItems()))
      ->andIf(AccessResult::allowedIfHasPermission($account, 'access checkout'))
      ->addCacheableDependency($order);

    return $access;
  }

  /**
   * Checks access to a particular checkout page.
   *
   * @param Drupal\commerce_order\Entity\OrderInterface $order
   *   The fully loaded order object represented on the checkout form.
   *
   * @return bool
   *   TRUE or FALSE indicating access.
   */
  protected function checkoutPageAccess(OrderInterface $order) {
    $checkout_flow = $this->checkoutOrderManager->getCheckoutFlow($order);
    $requested_step = $checkout_flow->getPlugin()->getStepId();
    $visible_steps = $checkout_flow->getPlugin()->getVisibleSteps();
    $visible_step_ids = array_keys($visible_steps);
    $first_step = reset($visible_step_ids);
    $order_step = $this->getOrderCheckoutStep($order);

    // If the order is not in checkout, return FALSE for any page but the
    // completion page.
    if ($order_step === 'complete' && $requested_step !== 'complete') {
      return FALSE;
    }

    // If the order is still in checkout, only allow access to pages that it is
    // currently on or has previously completed.
    if ($order_step !== 'complete') {

      // This is the page the user is currently on.
      if ($requested_step == $order_step) {
        return TRUE;
      }

      foreach ($visible_steps as $step_id => $step) {
        // Prevent access to later steps of the checkout process.
        if ($step_id === $order_step) {
          return FALSE;
        }

        // Check that there are back buttons in every pages between the current
        // page and the page the user wants to access.
        if (!isset($step['previous_label'])) {
          return FALSE;
        }

        // If this is the requested step and nothing intervened so far, give a
        // green light.
        if ($step_id == $requested_step) {
          return TRUE;
        }
      }
    }

    // We've now handled above cases where the user is trying to access a
    // checkout page other than the completion page for an order that is not in
    // a checkout status.  We then handled cases where the user is trying to
    // access any checkout page for orders in a checkout status.  We now turn
    // to cases where the user is accessing the complete page for any other
    // order state.
    elseif ($requested_step == 'complete') {
      // Don't allow completion page access for orders in the cart or canceled states.
      if ($order->cart->getString() || $order->getState()->getString() === 'canceled') {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Get the current checkout step of the order.
   *
   * @param Drupal\commerce_order\Entity\OrderInterface $order
   *   The fully loaded order object represented on the checkout form.
   *
   * @return string
   *   The checkout step id of the order. If the checkout_step property is
   *   empty then it returns the first visible checkout step id.
   */
  protected function getOrderCheckoutStep(OrderInterface $order) {
    $order_step = &drupal_static(__METHOD__ . '-' . $order->id());
    if (!isset($order_step)) {
      $order_step = $order->checkout_step->getString();
      // An empty $order_step means the checkout flow is at the first step.
      if (empty($order_step)) {
        $checkout_flow = $this->checkoutOrderManager->getCheckoutFlow($order);
        $visible_steps = $checkout_flow->getPlugin()->getVisibleSteps();
        $visible_step_ids = array_keys($visible_steps);
        $first_step = reset($visible_step_ids);
        $order_step = $first_step;
      }
    }
    return $order_step;
  }

}
