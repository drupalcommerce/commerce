<?php

namespace Drupal\commerce_price\Exception;

/**
 * This exception is thrown when attempting to perform math using two different currencies.
 * For example, adding a price in USD to another in EUR.
 */
class CurrencyMismatchException extends \RuntimeException {

}
