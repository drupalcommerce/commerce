<?php

namespace Drupal\Tests\commerce_checkout\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the checkout of an order.
 *
 * @group commerce
 */
class CheckoutFlowAdminTest extends CommerceBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['commerce_checkout'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer checkout flows',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests the checkout-workflow admin pages.
   */
  public function testCheckoutFlowAdminPaneWeights() {
    // Goto the administration page for Checkout Workflows.
    $this->drupalGet('/admin/commerce/config/checkout-flows/manage/default');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->pageTextContains('No pane is displayed');
  }

  /**
   * Test Checkout Label.
   */
  public function testCheckoutFlowAdminLabel() {
    $admin_url = '/admin/commerce/config/checkout-flows/manage/default';
    $form_field = 'label';
    $default_value = 'Default';
    $new_value = 'Changed label';

    // Goto the administration page for Checkout Workflows.
    $this->drupalGet($admin_url);
    $this->assertSession()->statusCodeEquals(200);

    // Validate default.
    $this->assertSession()->fieldValueEquals($form_field, $default_value);

    // Change the Label value.
    $values = [
      $form_field => $new_value,
    ];
    $this->submitForm($values, t('Save'));
    $this->assertSession()->pageTextContains($new_value);
  }

  /**
   * Tests Order summary view.
   */
  public function testCheckoutFlowAdminOrderSummaryView() {
    $admin_url = '/admin/commerce/config/checkout-flows/manage/default';
    $form_field = 'configuration[order_summary_view]';
    $default_value = 'commerce_checkout_order_summary';
    $new_value = '';

    // Goto the administration page for Checkout Workflows.
    $this->drupalGet($admin_url);
    $this->assertSession()->statusCodeEquals(200);

    // Validate that default value is 'commerce_checkout_order_summary'.
    $this->assertSession()->pageTextContains('Order summary view');
    $this->assertSession()->fieldExists($form_field);
    $this->assertSession()->fieldValueEquals($form_field, $default_value);
    $this->assertSession()->fieldValueNotEquals($form_field, $new_value);

    // Set to "-- None --".
    $values = [
      $form_field => $new_value,
    ];
    $this->submitForm($values, t('Save'));
    $this->drupalGet($admin_url);
    $this->assertSession()->fieldValueEquals($form_field, $new_value);

    // Set back to "Checkout Order Summary".
    $values = [
      $form_field => $default_value,
    ];
    $this->submitForm($values, t('Save'));
    $this->drupalGet($admin_url);
    $this->assertSession()->fieldValueEquals($form_field, $default_value);
  }

  /**
   * Tests Display checkout pages.
   */
  public function testCheckoutFlowAdminDisplayCheckoutProgress() {
    $admin_url = '/admin/commerce/config/checkout-flows/manage/default';
    $form_field = 'configuration[display_checkout_progress]';
    $default_value = '1';
    $new_value = '0';

    // Goto the administration page for Checkout Workflows.
    $this->drupalGet($admin_url);
    $this->assertSession()->statusCodeEquals(200);

    // Validate that default value is Enabled.
    $this->assertSession()->pageTextContains('Used by the checkout progress block to determine visibility');
    $this->assertSession()->fieldExists($form_field);
    $this->assertSession()->checkboxChecked($form_field);

    // Change to new-value.
    $values = [
      $form_field => $new_value,
    ];
    $this->submitForm($values, t('Save'));
    $this->drupalGet($admin_url);
    $this->assertSession()->checkboxNotChecked($form_field);
    $this->assertSession()->fieldValueEquals($form_field, $new_value);

    // Set back to default.
    $values = [
      $form_field => $default_value,
    ];
    $this->submitForm($values, t('Save'));
    $this->drupalGet($admin_url);
    $this->assertSession()->checkboxChecked($form_field);
    $this->assertSession()->fieldValueEquals($form_field, $default_value);
  }

}
