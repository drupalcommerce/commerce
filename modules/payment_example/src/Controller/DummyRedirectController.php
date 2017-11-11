<?php

namespace Drupal\commerce_payment_example\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This is a dummy controller for mocking an off-site gateway.
 */
class DummyRedirectController implements ContainerInjectionInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Constructs a new DummyRedirectController object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Callback method which accepts POST.
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function post() {
    $cancel = $this->currentRequest->request->get('cancel');
    $return = $this->currentRequest->request->get('return');
    $total = $this->currentRequest->request->get('total');

    if ($total > 20) {
      return new TrustedRedirectResponse($return);
    }

    return new TrustedRedirectResponse($cancel);
  }

  /**
   * Callback method which reacts to GET from a 302 redirect.
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function on302() {
    $cancel = $this->currentRequest->query->get('cancel');
    $return = $this->currentRequest->query->get('return');
    $total = $this->currentRequest->query->get('total');

    if ($total > 20) {
      return new TrustedRedirectResponse($return);
    }

    return new TrustedRedirectResponse($cancel);
  }

}
