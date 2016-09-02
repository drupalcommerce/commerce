<?php

namespace Drupal\commerce_payment_example\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for the example_onsite payment gateway.
 *
 * Onsite payment gateways allow the customer to enter credit card details
 * directly on the site. The details might be safely tokenized before they
 * reach the server (Braintree, Stripe, etc) or they might be transmitted
 * directly through the server (PayPal Payments Pro).
 *
 * The OnsitePaymentGatewayInterface is the base interface which all onsite
 * gateways implement. The other interfaces signal which additional capabilities
 * the gateway has. The gateway plugin is free to expose additional methods,
 * which would be defined below.
 */
interface OnsiteInterface extends OnsitePaymentGatewayInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface {

}
