<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

/**
 * Defines the base interface for on-site payment gateways.
 *
 * On-site payment gateways allow the customer to enter credit card details
 * directly on the site. The details might be safely tokenized before they
 * reach the server (Braintree, Stripe, etc) or they might be transmitted
 * directly through the server (PayPal Payments Pro).
 *
 * On-site payment flow:
 * 1) The customer enters checkout.
 * 2) The PaymentInformation checkout pane shows the "add-payment-method"
 *    plugin form, allowing the customer to enter their payment details.
 * 3) On submit, a payment method is created via createPaymentMethod()
 *    and attached to the customer and the order.
 * 4) The customer continues checkout, hits the "payment" checkout step.
 * 5) The PaymentProcess checkout pane calls createPayment(), which charges
 *    the provided payment method and creates a payment.
 *
 * If the payment method could not be charged (for example, because the credit
 * card's daily limit was breached), the customer is redirected back to the
 * checkout step that contains the PaymentInformation checkout pane, to provide
 * a different payment method.
 */
interface OnsitePaymentGatewayInterface extends PaymentGatewayInterface, SupportsStoredPaymentMethodsInterface, SupportsCreatingPaymentMethodsInterface {

}
