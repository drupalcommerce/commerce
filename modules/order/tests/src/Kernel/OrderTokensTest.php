<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Tests the order tokens.
 *
 * @group commerce
 */
class OrderTokensTest extends OrderKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'token',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['system']);
  }

  /**
   * Tests URL tokens for orders.
   *
   * @dataProvider tokensTestData
   */
  public function testTokens(string $test_token, string $expected_replacement) {
    $token = $this->container->get('token');
    $user = User::create([
      'uid' => '456',
      'name' => 'foobar',
      'mail' => 'foobar@example.com',
    ]);
    $user->enforceIsNew(FALSE);
    $order = Order::create([
      'order_id' => '123',
      'uid' => $user,
      'type' => 'default',
    ]);
    $order->enforceIsNew(FALSE);

    $token_data = ['commerce_order' => $order];
    $bubbleable_metadata = new BubbleableMetadata();
    $this->assertEquals($expected_replacement, $token->replace($test_token, $token_data, [], $bubbleable_metadata));
    $this->assertEquals(['commerce_order:123'], $bubbleable_metadata->getCacheTags());
  }

  /**
   * Test data for URL tokens.
   *
   * @return \Generator
   *   The test data.
   */
  public function tokensTestData(): \Generator {
    yield [
      '[commerce_order:order_id]',
      '123',
    ];
    yield [
      '[commerce_order:url]',
      'http://localhost/user/456/orders/123',
    ];
    yield [
      '[commerce_order:admin-url]',
      'http://localhost/admin/commerce/orders/123',
    ];
  }

}
