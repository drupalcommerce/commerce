<?php

namespace Drupal\commerce;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a class for obtaining system time.
 *
 * A copy of the Time class that exists in Drupal 8.3.x.
 *
 * @todo Replace with the core class once we start depending on 8.3.x.
 */
class Time implements TimeInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a Time object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * @{inheritdoc}
   */
  public function getRequestTime() {
    return $this->requestStack->getCurrentRequest()->server->get('REQUEST_TIME');
  }

  /**
   * @{inheritdoc}
   */
  public function getRequestMicroTime() {
    return $this->requestStack->getCurrentRequest()->server->get('REQUEST_TIME_FLOAT');
  }

  /**
   * @{inheritdoc}
   */
  public function getCurrentTime() {
    return time();
  }

  /**
   * @{inheritdoc}
   */
  public function getCurrentMicroTime() {
    return microtime(TRUE);
  }

}
