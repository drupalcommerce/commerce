<?php

namespace Drupal\commerce_cart\Exception;

/**
 * Thrown when attempting to create a duplicate cart.
 */
class DuplicateCartException extends \RuntimeException implements ExceptionInterface {}
