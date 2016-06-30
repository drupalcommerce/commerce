<?php

namespace Drupal\commerce_payment\Exception;

/**
 * Thrown when the payment gateway makes an invalid request.
 *
 * A request is invalid due to missing or invalid parameters, usually
 * indicating a bug in the plugin logic.
 */
class InvalidRequestException extends PaymentGatewayException {}
