<?php

namespace Drupal\commerce_payment_example\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;

/**
 * Provides the interface for the example_offsite payment gateway.
 *
 * The OffsitePaymentGatewayInterface is the base interface which all off-site
 * gateways implement. The other interfaces signal which additional capabilities
 * the gateway has. See OnsiteInterface for examples of such implementation.
 * The gateway plugin is free to expose additional methods, which would be
 * defined below.
 */
interface OffsiteInterface extends OffsitePaymentGatewayInterface {

}
