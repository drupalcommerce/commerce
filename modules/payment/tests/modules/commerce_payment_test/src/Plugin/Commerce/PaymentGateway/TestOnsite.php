<?php

namespace Drupal\commerce_payment_test\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment_example\Plugin\Commerce\PaymentGateway\Onsite;

/**
 * Provides the Test on-site payment gateway.
 *
 * This is a copy of example_onsite with a different display_label.
 *
 * @CommercePaymentGateway(
 *   id = "test_onsite",
 *   label = "Test (On-site)",
 *   display_label = "Test",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_payment_example\PluginForm\Onsite\PaymentMethodAddForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class TestOnsite extends Onsite {}
