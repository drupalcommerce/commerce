<?php

namespace Drupal\Tests\commerce_checkout\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the checkout of an order.
 *
 * @group commerce
 */
class CheckoutOrderTest extends CommerceBrowserTestBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_product',
    'commerce_order',
    'commerce_cart',
    'commerce_checkout',
    'commerce_checkout_test',
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_checkout_flow',
      'administer views',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->placeBlock('commerce_cart');
    $this->placeBlock('commerce_checkout_progress');

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 9.99,
        'currency_code' => 'USD',
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
      'stores' => [$this->store],
    ]);
  }

  /**
   * Tests checkout flow cache metadata.
   */
  public function testCacheMetadata() {
    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->assertSession()->pageTextContains('1 item');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
    $this->assertSession()->pageTextNotContains('Order Summary');
    $this->assertCheckoutProgressStep('Login');

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->container->get('entity_type.manager')->getStorage('commerce_order')->load(1);
    /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
    $checkout_flow = $this->container->get('entity_type.manager')->getStorage('commerce_checkout_flow')->load('default');

    // We're on a form, so no Page Cache.
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', NULL);
    // Dynamic page cache should be present, and a MISS.
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'MISS');

    // Assert cache tags bubbled.
    $cache_tags_header = $this->getSession()->getResponseHeader('X-Drupal-Cache-Tags');
    $this->assertTrue(strpos($cache_tags_header, 'commerce_order:' . $order->id()) !== FALSE);
    foreach ($order->getItems() as $item) {
      $this->assertTrue(strpos($cache_tags_header, 'commerce_order_item:' . $item->id()) !== FALSE);
    }
    foreach ($checkout_flow->getCacheTags() as $cache_tag) {
      $this->assertTrue(strpos($cache_tags_header, $cache_tag) !== FALSE);
    }

    $this->getSession()->reload();
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'HIT');

    // Saving the order should bust the cache.
    $this->container->get('commerce_order.order_refresh')->refresh($order);
    $order->save();

    $this->getSession()->reload();
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'MISS');
    $this->getSession()->reload();
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'HIT');

    // Saving the checkout flow configuration entity should bust the cache.
    $checkout_flow->save();

    $this->getSession()->reload();
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'MISS');
    $this->getSession()->reload();
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'HIT');
  }

  /**
   * Tests anonymous and authenticated checkout.
   */
  public function testCheckout() {
    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->assertSession()->pageTextContains('1 item');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
    $this->assertSession()->pageTextNotContains('Order Summary');

    $this->assertCheckoutProgressStep('Login');
    $this->submitForm([], 'Continue as Guest');
    $this->assertCheckoutProgressStep('Order information');
    $this->submitForm([
      'contact_information[email]' => 'guest@example.com',
      'contact_information[email_confirm]' => 'guest@example.com',
      'billing_information[profile][address][0][address][given_name]' => 'John',
      'billing_information[profile][address][0][address][family_name]' => 'Smith',
      'billing_information[profile][address][0][address][organization]' => 'Centarro',
      'billing_information[profile][address][0][address][address_line1]' => '9 Drupal Ave',
      'billing_information[profile][address][0][address][postal_code]' => '94043',
      'billing_information[profile][address][0][address][locality]' => 'Mountain View',
      'billing_information[profile][address][0][address][administrative_area]' => 'CA',
    ], 'Continue to review');
    $this->assertCheckoutProgressStep('Review');
    $this->assertSession()->pageTextContains('Contact information');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->submitForm([], 'Complete checkout');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('0 items');

    $order = Order::load(1);
    // Confirm that the profile hasn't been copied to the address book yet.
    $billing_profile = $order->getBillingProfile();
    $this->assertTrue($billing_profile->getData('copy_to_address_book'));
    $this->assertEmpty($billing_profile->getData('address_book_profile_id'));

    // Confirm that the profile has been copied after the order was assigned.
    $order_assignment = $this->container->get('commerce_order.order_assignment');
    $order_assignment->assign($order, $this->adminUser);
    $billing_profile = $this->reloadEntity($billing_profile);
    $this->assertEmpty($billing_profile->getData('copy_to_address_book'));
    $this->assertNotEmpty($billing_profile->getData('address_book_profile_id'));

    // Test second order.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->assertSession()->pageTextContains('1 item');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
    $this->assertCheckoutProgressStep('Order information');
    // Confirm that the information from the address book profile is rendered.
    $expected_address = [
      'given_name' => 'John',
      'family_name' => 'Smith',
      'organization' => 'Centarro',
      'address_line1' => '9 Drupal Ave',
      'postal_code' => '94043',
      'locality' => 'Mountain View',
      'administrative_area' => 'CA',
    ];
    $page = $this->getSession()->getPage();
    foreach ($expected_address as $property => $value) {
      $this->assertStringContainsString($value, $page->find('css', 'p.address')->getText());
      $this->assertSession()->fieldNotExists("billing_information[profile][address][0][address][$property]");
    }
    $this->assertSession()->fieldNotExists('billing_information[profile][copy_to_address_book]');

    $this->submitForm([], 'Continue to review');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->assertCheckoutProgressStep('Review');

    // Go back and forth.
    $this->getSession()->getPage()->clickLink('Go back');
    $this->assertCheckoutProgressStep('Order information');
    $this->getSession()->getPage()->pressButton('Continue to review');
    $this->assertCheckoutProgressStep('Review');

    $this->submitForm([], 'Complete checkout');
    $this->assertSession()->pageTextContains('Your order number is 2. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('0 items');

    $order = Order::load(2);
    // Confirm that the billing profile has the expected address.
    $expected_address += ['country_code' => 'US'];
    $billing_profile = $order->getBillingProfile();
    $this->assertEquals($expected_address, array_filter($billing_profile->get('address')->first()->toArray()));
    $this->assertEmpty($billing_profile->getData('copy_to_address_book'));
    $this->assertNotEmpty($billing_profile->getData('address_book_profile_id'));
  }

  /**
   * Tests that you can register from the login checkout pane.
   */
  public function testRegisterOrderCheckout() {
    $config = \Drupal::configFactory()->getEditable('commerce_checkout.commerce_checkout_flow.default');
    $config->set('configuration.panes.login.allow_guest_checkout', FALSE);
    $config->set('configuration.panes.login.allow_registration', TRUE);
    $config->save();

    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
    $this->assertSession()->pageTextContains('New Customer');
    $this->submitForm([
      'login[register][name]' => 'User name',
      'login[register][mail]' => 'guest@example.com',
      'login[register][password][pass1]' => 'pass',
      'login[register][password][pass2]' => 'pass',
    ], 'Create account and continue');
    $this->assertSession()->pageTextContains('Billing information');

    // Test account validation.
    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
    $this->assertSession()->pageTextContains('New Customer');

    $this->submitForm([
      'login[register][name]' => 'User name',
      'login[register][mail]' => '',
      'login[register][password][pass1]' => 'pass',
      'login[register][password][pass2]' => 'pass',
    ], 'Create account and continue');
    $this->assertSession()->pageTextContains('Email field is required.');

    $this->submitForm([
      'login[register][name]' => '',
      'login[register][mail]' => 'guest@example.com',
      'login[register][password][pass1]' => 'pass',
      'login[register][password][pass2]' => 'pass',
    ], 'Create account and continue');
    $this->assertSession()->pageTextContains('Username field is required.');

    $this->submitForm([
      'login[register][name]' => 'User name',
      'login[register][mail]' => 'guest@example.com',
      'login[register][password][pass1]' => '',
      'login[register][password][pass2]' => '',
    ], 'Create account and continue');
    $this->assertSession()->pageTextContains('Password field is required.');

    $this->submitForm([
      'login[register][name]' => 'User name double email',
      'login[register][mail]' => 'guest@example.com',
      'login[register][password][pass1]' => 'pass',
      'login[register][password][pass2]' => 'pass',
    ], 'Create account and continue');
    $this->assertSession()->pageTextContains('The email address guest@example.com is already taken.');

    $this->submitForm([
      'login[register][name]' => 'User @#.``^ ù % name invalid',
      'login[register][mail]' => 'guest2@example.com',
      'login[register][password][pass1]' => 'pass',
      'login[register][password][pass2]' => 'pass',
    ], 'Create account and continue');
    $this->assertSession()->pageTextContains('The username contains an illegal character.');

    $this->submitForm([
      'login[register][name]' => 'User name',
      'login[register][mail]' => 'guest2@example.com',
      'login[register][password][pass1]' => 'pass',
      'login[register][password][pass2]' => 'pass',
    ], 'Create account and continue');
    $this->assertSession()->pageTextContains('The username User name is already taken.');
  }

  /**
   * Tests that you can register from the checkout pane with custom user fields.
   */
  public function testRegisterOrderCheckoutWithCustomUserFields() {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_user_field',
      'entity_type' => 'user',
      'type' => 'string',
      'cardinality' => 1,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'label' => 'Custom user field',
      'bundle' => 'user',
      'required' => TRUE,
    ]);
    $field->save();
    $form_display = commerce_get_entity_display('user', 'user', 'form');
    $form_display->setComponent('test_user_field', ['type' => 'string_textfield']);
    $form_display->save();

    $config = \Drupal::configFactory()->getEditable('commerce_checkout.commerce_checkout_flow.default');
    $config->set('configuration.panes.login.allow_guest_checkout', FALSE);
    $config->set('configuration.panes.login.allow_registration', TRUE);
    $config->save();

    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
    $this->assertSession()->pageTextContains('New Customer');
    $this->submitForm([
      'login[register][name]' => 'User name',
      'login[register][mail]' => 'guest@example.com',
      'login[register][password][pass1]' => 'pass',
      'login[register][password][pass2]' => 'pass',
    ], 'Create account and continue');
    $this->assertSession()->pageTextContains('Custom user field field is required.');

    $this->submitForm([
      'login[register][name]' => 'User name',
      'login[register][mail]' => 'guest@example.com',
      'login[register][password][pass1]' => 'pass',
      'login[register][password][pass2]' => 'pass',
      'login[register][test_user_field][0][value]' => 'test_user_field_value',
    ], 'Create account and continue');
    $this->assertSession()->pageTextContains('Billing information');

    $accounts = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->loadByProperties(['mail' => 'guest@example.com']);
    /** @var \Drupal\user\UserInterface $account */
    $account = reset($accounts);
    $this->assertTrue($account->isActive());
    $this->assertEquals('test_user_field_value', $account->get('test_user_field')->value);
  }

  /**
   * Tests that you can register after completing guest checkout.
   */
  public function testRegistrationAfterGuestOrderCheckout() {
    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');

    // Checkout as guest.
    $this->assertCheckoutProgressStep('Login');
    $this->submitForm([], 'Continue as Guest');
    $this->assertCheckoutProgressStep('Order information');
    $this->submitForm([
      'contact_information[email]' => 'guest@example.com',
      'contact_information[email_confirm]' => 'guest@example.com',
      'billing_information[profile][address][0][address][given_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][family_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][organization]' => $this->randomString(),
      'billing_information[profile][address][0][address][address_line1]' => $this->randomString(),
      'billing_information[profile][address][0][address][postal_code]' => '94043',
      'billing_information[profile][address][0][address][locality]' => 'Mountain View',
      'billing_information[profile][address][0][address][administrative_area]' => 'CA',
    ], 'Continue to review');
    $this->assertCheckoutProgressStep('Review');
    $this->assertSession()->pageTextContains('Contact information');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->submitForm([], 'Complete checkout');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    $this->assertSession()->pageTextContains('Create your account');
    $this->submitForm([
      'completion_register[name]' => 'User name',
      'completion_register[pass][pass1]' => 'pass',
      'completion_register[pass][pass2]' => 'pass',
    ], 'Create account');
    $this->assertSession()->pageTextContains('Registration successful. You are now logged in.');

    // Log out and try to login again with the chosen password.
    $this->drupalLogout();
    $accounts = \Drupal::service('entity_type.manager')->getStorage('user')->loadByProperties(['mail' => 'guest@example.com']);
    /** @var \Drupal\user\UserInterface $account */
    $account = reset($accounts);
    $this->assertTrue($account->isActive());
    $account->passRaw = 'pass';
    $this->drupalLogin($account);

    // Checkout again as guest to test account validation.
    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
    $this->assertCheckoutProgressStep('Login');
    $this->submitForm([], 'Continue as Guest');
    $this->assertCheckoutProgressStep('Order information');
    $this->submitForm([
      'contact_information[email]' => 'guest2@example.com',
      'contact_information[email_confirm]' => 'guest2@example.com',
      'billing_information[profile][address][0][address][given_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][family_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][organization]' => $this->randomString(),
      'billing_information[profile][address][0][address][address_line1]' => $this->randomString(),
      'billing_information[profile][address][0][address][postal_code]' => '94043',
      'billing_information[profile][address][0][address][locality]' => 'Mountain View',
      'billing_information[profile][address][0][address][administrative_area]' => 'CA',
    ], 'Continue to review');
    $this->assertCheckoutProgressStep('Review');
    $this->assertSession()->pageTextContains('Contact information');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->submitForm([], 'Complete checkout');
    $this->assertSession()->pageTextContains('Your order number is 2. You can view your order on your account page when logged in.');

    $this->submitForm([
      'completion_register[name]' => '',
      'completion_register[pass][pass1]' => 'pass',
      'completion_register[pass][pass2]' => 'pass',
    ], 'Create account');
    $this->assertSession()->pageTextContains('You must enter a username.');

    $this->submitForm([
      'completion_register[name]' => 'User name',
      'completion_register[pass][pass1]' => '',
      'completion_register[pass][pass2]' => '',
    ], 'Create account');
    $this->assertSession()->pageTextContains('Password field is required.');

    $this->submitForm([
      'completion_register[name]' => 'User @#.``^ ù % name invalid',
      'completion_register[pass][pass1]' => 'pass',
      'completion_register[pass][pass2]' => 'pass',
    ], 'Create account');
    $this->assertSession()->pageTextContains('The username contains an illegal character.');

    $this->submitForm([
      'completion_register[name]' => 'User name',
      'completion_register[pass][pass1]' => 'pass',
      'completion_register[pass][pass2]' => 'pass',
    ], 'Create account');
    $this->assertSession()->pageTextContains('The username User name is already taken.');
  }

  /**
   * Tests custom user fields are respected on registration after checkout.
   */
  public function testRegistrationAfterGuestOrderCheckoutWithCustomUserFields() {
    // Create a field on 'user' entity type.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_user_field',
      'entity_type' => 'user',
      'type' => 'string',
      'cardinality' => 1,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'label' => 'Custom user field',
      'bundle' => 'user',
      'required' => TRUE,
    ]);
    $field->save();
    $form_display = commerce_get_entity_display('user', 'user', 'form');
    $form_display->setComponent('test_user_field', ['type' => 'string_textfield']);
    $form_display->save();

    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');

    // Checkout as guest.
    $this->assertCheckoutProgressStep('Login');
    $this->submitForm([], 'Continue as Guest');
    $this->assertCheckoutProgressStep('Order information');
    $this->submitForm([
      'contact_information[email]' => 'guest@example.com',
      'contact_information[email_confirm]' => 'guest@example.com',
      'billing_information[profile][address][0][address][given_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][family_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][organization]' => $this->randomString(),
      'billing_information[profile][address][0][address][address_line1]' => $this->randomString(),
      'billing_information[profile][address][0][address][postal_code]' => '94043',
      'billing_information[profile][address][0][address][locality]' => 'Mountain View',
      'billing_information[profile][address][0][address][administrative_area]' => 'CA',
    ], 'Continue to review');
    $this->assertCheckoutProgressStep('Review');
    $this->assertSession()->pageTextContains('Contact information');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->submitForm([], 'Complete checkout');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    $this->assertSession()->pageTextContains('Create your account');
    $this->submitForm([
      'completion_register[name]' => 'User name',
      'completion_register[pass][pass1]' => 'pass',
      'completion_register[pass][pass2]' => 'pass',
    ], 'Create account');
    $this->assertSession()->pageTextNotContains('Registration successful. You are now logged in.');
    $this->assertSession()->pageTextContains('Custom user field field is required.');

    $this->submitForm([
      'completion_register[name]' => 'User name',
      'completion_register[pass][pass1]' => 'pass',
      'completion_register[pass][pass2]' => 'pass',
      'completion_register[test_user_field][0][value]' => 'test_user_field_value',
    ], 'Create account');
    $this->assertSession()->pageTextContains('Registration successful. You are now logged in.');

    $accounts = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->loadByProperties(['mail' => 'guest@example.com']);
    /** @var \Drupal\user\UserInterface $account */
    $account = reset($accounts);
    $this->assertTrue($account->isActive());
    $this->assertEquals('test_user_field_value', $account->get('test_user_field')->value);
  }

  /**
   * Tests redirection after registering at the end of checkout.
   */
  public function testRedirectAfterRegistrationOnCheckout() {
    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');

    // Checkout as guest.
    $this->assertCheckoutProgressStep('Login');
    $this->submitForm([], 'Continue as Guest');
    $this->assertCheckoutProgressStep('Order information');
    $this->submitForm([
      'contact_information[email]' => 'guest@example.com',
      'contact_information[email_confirm]' => 'guest@example.com',
      'billing_information[profile][address][0][address][given_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][family_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][organization]' => $this->randomString(),
      'billing_information[profile][address][0][address][address_line1]' => $this->randomString(),
      'billing_information[profile][address][0][address][postal_code]' => '94043',
      'billing_information[profile][address][0][address][locality]' => 'Mountain View',
      'billing_information[profile][address][0][address][administrative_area]' => 'CA',
    ], 'Continue to review');
    $this->assertCheckoutProgressStep('Review');
    $this->assertSession()->pageTextContains('Contact information');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->submitForm([], 'Complete checkout');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    $this->assertSession()->pageTextContains('Create your account');
    $this->submitForm([
      'completion_register[name]' => 'bob_redirect',
      'completion_register[pass][pass1]' => 'pass',
      'completion_register[pass][pass2]' => 'pass',
    ], 'Create account');
    $this->assertSession()->pageTextContains('Registration successful. You are now logged in.');

    // Confirm that a redirect had taken place.
    $url = Url::fromRoute('entity.user.edit_form', ['user' => 3], ['absolute' => TRUE]);
    $this->assertSession()->addressEquals($url->toString());
  }

  /**
   * Tests checkout behaviour after a cart update.
   */
  public function testCheckoutFlowOnCartUpdate() {
    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->getSession()->getPage()->findLink('your cart')->click();
    // Submit the form until review.
    $this->submitForm([], 'Checkout');
    $this->assertSession()->elementContains('css', 'h1.page-title', 'Order information');
    $this->assertSession()->elementNotContains('css', 'h1.page-title', 'Review');
    $this->submitForm([
      'billing_information[profile][address][0][address][given_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][family_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][organization]' => $this->randomString(),
      'billing_information[profile][address][0][address][address_line1]' => $this->randomString(),
      'billing_information[profile][address][0][address][postal_code]' => '94043',
      'billing_information[profile][address][0][address][locality]' => 'Mountain View',
      'billing_information[profile][address][0][address][administrative_area]' => 'CA',
    ], 'Continue to review');
    $this->assertSession()->elementContains('css', 'h1.page-title', 'Review');
    // By default the checkout step is preserved upon return.
    $this->drupalGet('/checkout/1');
    $this->assertSession()->elementContains('css', 'h1.page-title', 'Review');

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 9.99,
        'currency_code' => 'USD',
      ],
    ]);
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product2 = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
      'stores' => [$this->store],
    ]);
    // Adding a new product to the cart resets the checkout step.
    $this->drupalGet($product2->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->getSession()->getPage()->findLink('your cart')->click();
    $this->submitForm([], 'Checkout');
    $this->assertSession()->elementContains('css', 'h1.page-title', 'Order information');
    $this->assertSession()->elementNotContains('css', 'h1.page-title', 'Review');

    // Removing a product from the cart resets the checkout step.
    $this->submitForm([], 'Continue to review');
    $this->drupalGet('/cart');
    $this->submitForm([], 'Remove');
    $this->submitForm([], 'Checkout');
    $this->assertSession()->elementContains('css', 'h1.page-title', 'Order information');
    $this->assertSession()->elementNotContains('css', 'h1.page-title', 'Review');
  }

  /**
   * Tests that login works even if the registration form has a required field.
   */
  public function testLoginWithRequiredRegistrationField() {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_user_field',
      'entity_type' => 'user',
      'type' => 'string',
      'cardinality' => 1,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'label' => 'Custom user field',
      'bundle' => 'user',
      'required' => TRUE,
    ]);
    $field->save();
    $form_display = commerce_get_entity_display('user', 'user', 'form');
    $form_display->setComponent('test_user_field', ['type' => 'string_textfield']);
    $form_display->save();

    $config = \Drupal::configFactory()->getEditable('commerce_checkout.commerce_checkout_flow.default');
    $config->set('configuration.panes.login.allow_guest_checkout', FALSE);
    $config->set('configuration.panes.login.allow_registration', TRUE);
    $config->save();

    $this->drupalLogout();
    $permissions = [
      'access checkout',
      'view commerce_product',
    ];
    $this->drupalCreateUser($permissions, 'testuser', FALSE, ['pass' => 'pass']);

    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');

    $this->submitForm([
      'login[returning_customer][name]' => 'testuser',
      'login[returning_customer][password]' => 'pass',
    ], 'Log in');
    $this->assertCheckoutProgressStep('Order information');
  }

  /**
   * Tests a customized checkout complete message.
   *
   * @group debug
   */
  public function testCustomCheckoutCompletionMessage() {
    // Create Full HTML text format.
    $full_html_format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
    ]);
    $full_html_format->save();

    $config = \Drupal::configFactory()->getEditable('commerce_checkout.commerce_checkout_flow.default');
    $config->set('configuration.panes.completion_message.message.value', '<h1>Your order number is [commerce_order:order_number].</h1><p>Click here you view your order: [commerce_order:url].</p>');
    $config->set('configuration.panes.completion_message.message.format', 'full_html');
    $config->save();

    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');

    $this->submitForm([], 'Continue as Guest');
    $this->submitForm([
      'contact_information[email]' => 'guest@example.com',
      'contact_information[email_confirm]' => 'guest@example.com',
      'billing_information[profile][address][0][address][given_name]' => 'John',
      'billing_information[profile][address][0][address][family_name]' => 'Smith',
      'billing_information[profile][address][0][address][organization]' => 'Centarro',
      'billing_information[profile][address][0][address][address_line1]' => '9 Drupal Ave',
      'billing_information[profile][address][0][address][postal_code]' => '94043',
      'billing_information[profile][address][0][address][locality]' => 'Mountain View',
      'billing_information[profile][address][0][address][administrative_area]' => 'CA',
    ], 'Continue to review');
    $this->submitForm([], 'Complete checkout');

    $expected_order_url = Url::fromRoute('entity.commerce_order.user_view', [
      'commerce_order' => 1,
      'user' => 0,
    ], ['absolute' => TRUE]);
    // We have text seperated by <h1> and <p> tags, so they appear individually.
    $this->assertSession()->pageTextNotContains("Your order number is 1. Click here you view your order: {$expected_order_url->toString()}.");
    $this->assertSession()->pageTextContains('Your order number is 1.');
    $this->assertSession()->pageTextContains("Click here you view your order: {$expected_order_url->toString()}.");

  }

  /**
   * Asserts the current step in the checkout progress block.
   *
   * @param string $expected
   *   The expected value.
   */
  protected function assertCheckoutProgressStep($expected) {
    $current_step = $this->getSession()->getPage()->find('css', '.checkout-progress--step__current')->getText();
    $this->assertEquals($expected, $current_step);
  }

}
