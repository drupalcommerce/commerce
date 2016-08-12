<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

/**
 * Defines the base interface for onsite payment gateways.
 */
interface OnsitePaymentGatewayInterface extends PaymentGatewayInterface, SupportsStoredPaymentMethods {
}
