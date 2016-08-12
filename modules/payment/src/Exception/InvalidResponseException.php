<?php

namespace Drupal\commerce_payment\Exception;

/**
 * Thrown when the payment gateway receives an invalid response.
 *
 * The API endpoint might be down, throwing an error, or returning a response
 * whose signature can't be verified.
 */
class InvalidResponseException extends PaymentGatewayException {}
