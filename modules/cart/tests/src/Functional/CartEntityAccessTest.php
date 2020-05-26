<?php

namespace Drupal\Tests\commerce_cart\Functional;

use Behat\Mink\Driver\GoutteDriver;
use Behat\Mink\Session;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\user\RoleInterface;

/**
 * Tests cart access.
 *
 * @group commerce
 */
class CartEntityAccessTest extends CartBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_checkout',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access checkout']);
  }

  /**
   * Tests that users with the view permission can view their own carts.
   */
  public function testViewAccess() {
    $customer = $this->drupalCreateUser(['access checkout', 'view own commerce_order']);

    // Ensure that vaccess checks are respected even if anonymous users have
    // permission to view their own orders.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['view own commerce_order']);

    // Authorized cart.
    $cart = \Drupal::service('commerce_cart.cart_provider')->createCart('default', $this->store, $customer);
    assert($cart instanceof OrderInterface);

    $this->drupalLogin($customer);
    $this->drupalGet('user/' . $customer->id() . '/orders/' . $cart->id());
    $this->assertSession()->statusCodeEquals(403);

    $this->switchSession('anonymous');
    $this->drupalGet('user/' . $customer->id() . '/orders/' . $cart->id());
    $this->assertSession()->statusCodeEquals(403);

    $cart->getState()->applyTransitionById('place');
    $cart->save();

    // User can now see placed cart.
    $this->mink->setDefaultSessionName('default');
    $this->drupalGet('user/' . $customer->id() . '/orders/' . $cart->id());
    $this->assertSession()->statusCodeEquals(200);

    $this->switchSession('anonymous');
    $this->drupalGet('user/' . $customer->id() . '/orders/' . $cart->id());
    $this->assertSession()->statusCodeEquals(403);

    // Anonymous active cart.
    $this->drupalPostForm('product/' . $this->variation->getProductId(), [], 'Add to cart');

    $this->mink->setDefaultSessionName('default');
    $this->drupalGet('user/0/orders/3');
    $this->assertSession()->statusCodeEquals(403);

    $this->switchSession('anonymous2');
    $this->drupalGet('user/0/orders/3');
    $this->assertSession()->statusCodeEquals(403);

    $this->mink->setDefaultSessionName('anonymous');
    $this->drupalGet('user/0/orders/3');
    $this->assertSession()->statusCodeEquals(403);

    // Anonymous completed cart.
    $this->drupalGet('checkout/3/login');
    $this->submitForm([], 'Continue as Guest');
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
    $this->submitForm([], 'Complete checkout');

    $this->drupalGet('user/0/orders/3');
    $this->assertSession()->statusCodeEquals(200);

    $this->mink->setDefaultSessionName('default');
    $this->drupalGet('user/0/orders/3');
    $this->assertSession()->statusCodeEquals(403);

    $this->mink->setDefaultSessionName('anonymous2');
    $this->drupalGet('user/0/orders/3');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests order view access without a "view own commerce_order" permission.
   */
  public function testViewAccessWithoutViewPermission() {
    $customer = $this->drupalCreateUser(['access checkout']);
    user_role_revoke_permissions(RoleInterface::ANONYMOUS_ID, ['view own commerce_order']);
    // Authorized cart.
    $cart = \Drupal::service('commerce_cart.cart_provider')->createCart('default', $this->store, $customer);

    $this->drupalLogin($customer);
    $this->drupalGet('user/' . $customer->id() . '/orders/' . $cart->id());
    $this->assertSession()->statusCodeEquals(403);

    $this->switchSession('anonymous');
    $this->drupalGet('user/' . $customer->id() . '/orders/' . $cart->id());
    $this->assertSession()->statusCodeEquals(403);

    // Anonymous active cart.
    $this->drupalPostForm('product/' . $this->variation->getProductId(), [], 'Add to cart');

    $this->mink->setDefaultSessionName('default');
    $this->drupalGet('user/0/orders/3');
    $this->assertSession()->statusCodeEquals(403);

    $this->switchSession('anonymous2');
    $this->drupalGet('user/0/orders/3');
    $this->assertSession()->statusCodeEquals(403);

    $this->switchSession('anonymous');
    $this->drupalGet('user/0/orders/3');
    $this->assertSession()->statusCodeEquals(403);

    // Anonymous completed cart.
    $this->drupalGet('checkout/3/login');
    $this->submitForm([], 'Continue as Guest');
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
    $this->submitForm([], 'Complete checkout');

    // Anonymous users can view their completed orders.
    $this->drupalGet('user/0/orders/3');
    $this->assertSession()->statusCodeEquals(200);

    $this->mink->setDefaultSessionName('default');
    $this->drupalGet('user/0/orders/3');
    $this->assertSession()->statusCodeEquals(403);

    $this->mink->setDefaultSessionName('anonymous2');
    $this->drupalGet('user/0/orders/3');
    $this->assertSession()->statusCodeEquals(403);

    // Authenticated completed cart.
    $cart->getState()->applyTransitionById('place');
    $cart->save();

    $this->switchSession('anonymous');
    $this->drupalGet('user/' . $customer->id() . '/orders/' . $cart->id());
    $this->assertSession()->statusCodeEquals(403);

    // Customers always see their completed orders when using the cart module.
    $this->mink->setDefaultSessionName('default');
    $this->drupalGet('user/' . $customer->id() . '/orders/' . $cart->id());
    $this->assertSession()->statusCodeEquals(200);

    $this->mink->setDefaultSessionName('anonymous2');
    $this->drupalGet('user/' . $customer->id() . '/orders/' . $cart->id());
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that cart access does not grant administrative access.
   */
  public function testAdministrativeAccess() {
    $customer = $this->drupalCreateUser(['view own commerce_order']);
    // Authorized cart.
    $cart = \Drupal::service('commerce_cart.cart_provider')->createCart('default', $this->store, $customer);
    assert($cart instanceof OrderInterface);

    $this->drupalLogin($customer);
    foreach ($cart->getEntityType()->getLinkTemplates() as $rel => $link_template) {
      $this->drupalGet($cart->toUrl($rel));
      $this->assertSession()->statusCodeEquals(403);
    }

    // Anonymous active cart.
    $this->switchSession('anonymous');
    $this->drupalPostForm('product/' . $this->variation->getProductId(), [], 'Add to cart');
    $cart = Order::load(3);
    foreach ($cart->getEntityType()->getLinkTemplates() as $rel => $link_template) {
      $this->drupalGet($cart->toUrl($rel));
      $this->assertSession()->statusCodeEquals(403);
    }
  }

  /**
   * Switches to a different session.
   *
   * @param string $name
   *   The name of the session to switch to.
   */
  protected function switchSession($name) {
    $create_session = !$this->mink->hasSession($name);
    if ($create_session) {
      $this->mink->registerSession($name, new Session(new GoutteDriver()));
    }
    $this->mink->setDefaultSessionName($name);

    if ($create_session) {
      // Visit the front page to initialise the session.
      $this->initFrontPage();
    }
  }

}
