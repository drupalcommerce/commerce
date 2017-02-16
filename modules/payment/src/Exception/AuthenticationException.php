<?php

namespace Drupal\commerce_payment\Exception;

/**
 * Thrown when the request can't not be properly authenticated.
 *
 * Usually indicates missing or invalid API keys.
 */
class AuthenticationException extends InvalidRequestException {}
