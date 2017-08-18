<?php

namespace Drupal\Tests\commerce_product\Functional;

use Drupal\Core\Session\AccountInterface;

/**
 * Tests product access for published and unpublished products.
 *
 * @group commerce
 */
class ProductPublishedAccessTest extends ProductBrowserTestBase {

  /**
   * The product to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => $this->stores,
      'body' => ['value' => 'Testing product variation field injection!'],
      'variations' => [
        $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'sku' => 'TEST001',
          'price' => [
            'number' => 999,
            'currency_code' => 'USD',
          ],
        ]),
        $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'sku' => 'TEST002',
          'price' => [
            'number' => 999,
            'currency_code' => 'USD',
          ],
        ]),
      ],
    ]);
  }

  /**
   * Tests the view published permission.
   */
  public function testPublishedAccess() {
    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl());
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($this->createUser());
    $this->drupalGet($this->product->toUrl());
    $this->assertSession()->statusCodeEquals(200);

    user_role_revoke_permissions(AccountInterface::ANONYMOUS_ROLE, ['view published commerce_product']);
    $this->drupalLogout();
    $this->drupalGet($this->product->toUrl());
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->createUser());
    $this->drupalGet($this->product->toUrl());
    $this->assertSession()->statusCodeEquals(200);

    user_role_revoke_permissions(AccountInterface::AUTHENTICATED_ROLE, ['view published commerce_product']);
    $this->drupalLogin($this->createUser());
    $this->drupalGet($this->product->toUrl());
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->product->toUrl());
    $this->assertSession()->statusCodeEquals(200);
  }

}
