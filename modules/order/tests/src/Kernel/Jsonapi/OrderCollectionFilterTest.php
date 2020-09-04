<?php

namespace Drupal\Tests\commerce_order\Kernel\Jsonapi;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;
use Drupal\user\Entity\Role;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group commerce_order
 */
class OrderCollectionFilterTest extends OrderKernelTestBase {

  /**
   * The test customer with orders for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  private $testOrderCustomer;

  /**
   * The other test customer.
   *
   * @var \Drupal\user\UserInterface
   */
  private $testOtherCustomer;

  /**
   * The test draft order UUID.
   */
  const ORDER_CUSTOMER_DRAFT_UUID = '3b7ad95f-0f1c-49d9-83d5-a92460fc82f1';

  /**
   * The test completed order UUID.
   */
  const ORDER_CUSTOMER_COMPLETED_UUID = '56843d3c-31ec-40b3-8d63-38154c8a95c6';

  /**
   * The order customer completed order UUID.
   */
  const OTHER_CUSTOMER_COMPLETED_UUID = 'f3feb4c5-5266-4f74-8c21-9c02185807db';

  /**
   * The anonymous order completed UUID.
   */
  const ANONYMOUS_COMPLETED_UUID = '310a3cee-787d-43ef-b2b7-a7a37e32080a';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'serialization',
    'jsonapi',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig('user');
    $this->grantPermissions(Role::load(Role::AUTHENTICATED_ID), ['view own commerce_order']);

    // Create uid1.
    $this->createUser();
    $this->testOrderCustomer = $this->createUser();
    $this->testOtherCustomer = $this->createUser();
  }

  /**
   * Tests filtering orders.
   *
   * @dataProvider filterDataParameters
   */
  public function testCustomerOrderCollectionFiltering(
    string $user_type,
    int $expected_unfiltered_count,
    array $expected_unfiltered_uuids,
    int $expected_filtered_count,
    array $expected_filtered_uuids
  ) {
    $user_ids = [
      'order_customer' => $this->testOrderCustomer->id(),
      'other_customer' => $this->testOtherCustomer->id(),
      'guest_customer' => 0,
      'guest_customer_with_permission' => 0,
      'admin_user' => $this->createUser([], ['administer commerce_order'])->id(),
      'view_user' => $this->createUser([], ['view commerce_order'])->id(),
    ];
    $this->assertArrayHasKey($user_type, $user_ids);
    if ($user_type === 'guest_customer_with_permission') {
      $this->grantPermissions(Role::load(Role::ANONYMOUS_ID), ['view own commerce_order']);
    }

    $this->generateTestOrders();
    $url = Url::fromRoute('jsonapi.commerce_order--default.collection');

    $this->container->get('session')->set('uid', $user_ids[$user_type]);
    $document = $this->doRequest($url);
    $this->assertArrayHasKey('data', $document);
    $this->assertCount($expected_unfiltered_count, $document['data'], var_export($document['data'], TRUE));
    $this->assertEquals($expected_unfiltered_uuids, array_map(static function (array $item) {
      return $item['id'];
    }, $document['data']));

    $url->setOption('query', [
      'filter' => [
        'state' => 'completed',
      ],
    ]);
    $document = $this->doRequest($url);
    $this->assertArrayHasKey('data', $document);
    $this->assertCount($expected_filtered_count, $document['data'], var_export($document['data'], TRUE));
    $this->assertEquals($expected_filtered_uuids, array_map(static function (array $item) {
      return $item['id'];
    }, $document['data']));
  }

  public function filterDataParameters(): \Generator {
    yield [
      'order_customer',
      2,
      [self::ORDER_CUSTOMER_DRAFT_UUID, self::ORDER_CUSTOMER_COMPLETED_UUID],
      1,
      [self::ORDER_CUSTOMER_COMPLETED_UUID],
    ];
    yield [
      'other_customer',
      1,
      [self::OTHER_CUSTOMER_COMPLETED_UUID],
      1,
      [self::OTHER_CUSTOMER_COMPLETED_UUID],
    ];
    yield [
      'guest_customer',
      0,
      [],
      0,
      [],
    ];
    yield [
      'guest_customer_with_permission',
      0,
      [],
      0,
      [],
    ];
    yield [
      'admin_user',
      4,
      [
        self::ORDER_CUSTOMER_DRAFT_UUID,
        self::ORDER_CUSTOMER_COMPLETED_UUID,
        self::OTHER_CUSTOMER_COMPLETED_UUID,
        self::ANONYMOUS_COMPLETED_UUID,
      ],
      3,
      [
        self::ORDER_CUSTOMER_COMPLETED_UUID,
        self::OTHER_CUSTOMER_COMPLETED_UUID,
        self::ANONYMOUS_COMPLETED_UUID,
      ],
    ];
    yield [
      'view_user',
      4,
      [
        self::ORDER_CUSTOMER_DRAFT_UUID,
        self::ORDER_CUSTOMER_COMPLETED_UUID,
        self::OTHER_CUSTOMER_COMPLETED_UUID,
        self::ANONYMOUS_COMPLETED_UUID,
      ],
      3,
      [
        self::ORDER_CUSTOMER_COMPLETED_UUID,
        self::OTHER_CUSTOMER_COMPLETED_UUID,
        self::ANONYMOUS_COMPLETED_UUID,
      ],
    ];
  }

  /**
   * Generates four test orders.
   *
   * 1. Draft order owned by test customer.
   * 2. Completed order owned by test customer.
   * 3. Completed order owned by other customer.
   * 4. Completed anonymous order.
   */
  private function generateTestOrders() {
    Order::create([
      'uuid' => self::ORDER_CUSTOMER_DRAFT_UUID,
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'draft',
      'mail' => $this->testOrderCustomer->getEmail(),
      'uid' => $this->testOrderCustomer->id(),
      'ip_address' => '127.0.0.1',
      'order_items' => [$this->generateOrderItem()],
    ])->save();
    Order::create([
      'uuid' => self::ORDER_CUSTOMER_COMPLETED_UUID,
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'completed',
      'mail' => $this->testOrderCustomer->getEmail(),
      'uid' => $this->testOrderCustomer->id(),
      'ip_address' => '127.0.0.1',
      'order_items' => [$this->generateOrderItem()],
    ])->save();
    Order::create([
      'uuid' => self::OTHER_CUSTOMER_COMPLETED_UUID,
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'completed',
      'mail' => $this->testOtherCustomer->getEmail(),
      'uid' => $this->testOtherCustomer->id(),
      'ip_address' => '127.0.0.1',
      'order_items' => [$this->generateOrderItem()],
    ])->save();
    Order::create([
      'uuid' => self::ANONYMOUS_COMPLETED_UUID,
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'completed',
      'mail' => 'foo@bar.com',
      'uid' => 0,
      'ip_address' => '127.0.0.1',
      'order_items' => [$this->generateOrderItem()],
    ])->save();
  }

  /**
   * Generates a test order item.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The order item.
   */
  private function generateOrderItem() {
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    return $this->reloadEntity($order_item);
  }

  /**
   * Does a request.
   *
   * @param \Drupal\Core\Url $url
   *   The URL.
   *
   * @return array
   *   The decoded response JSON.
   */
  private function doRequest(Url $url) {
    $request = Request::create($url->toString(), 'GET');
    $request->setSession($this->container->get('session'));
    $session_cookie_name = 'SESS' . substr(hash('sha256', drupal_valid_test_ua()), 0, 32);
    $request->cookies->set($session_cookie_name, $request->getSession()->getId());
    $request->headers->set('Accept', 'application/vnd.api+json');
    $response = $this->container->get('http_kernel')->handle($request);
    assert($response instanceof Response);
    return Json::decode($response->getContent());
  }

}
