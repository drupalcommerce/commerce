<?php

namespace Drupal\commerce_order\Exception;

/**
 * Thrown when attempting to save an order with wrong version.
 */
class OrderVersionMismatchException extends \RuntimeException implements ExceptionInterface { }
