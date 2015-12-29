<?php

/**
 * @file
 * Contains \Drupal\Tests\commerce_order\Kernel\Entity\OrderTest.
 */

namespace Drupal\Tests\commerce_order\Kernel\Entity;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\LineItem;
use Drupal\commerce_order\Entity\LineItemType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\profile\Entity\Profile;

/**
 * Tests the Order entity.
 *
 * @coversDefaultClass \Drupal\commerce_order\Entity\Order
 * @group commerce
 */
class OrderTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'field', 'options', 'user', 'views',
                            'address',  'profile', 'state_machine',
                            'inline_entity_form',  'commerce', 'commerce_price',
                            'commerce_store', 'commerce_product',
                            'commerce_order'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('commerce_order');
    $this->installEntitySchema('user');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_line_item');
    // A line item type that doesn't need a purchasable entity, for simplicity.
    LineItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();
  }

  /**
   * @covers ::getState
   * @covers ::getOrderNumber
   * @covers ::setOrderNumber
   * @covers ::getEmail
   * @covers ::setEmail
   * @covers ::getIpAddress
   * @covers ::setIpAddress
   * @covers ::getLineItems
   * @covers ::setLineItems
   * @covers ::hasLineItems
   * @covers ::addLineItem
   * @covers ::removeLineItem
   * @covers ::hasLineItem
   * @covers ::getBillingProfile
   * @covers ::setBillingProfile
   * @covers ::getBillingProfileId
   * @covers ::setBillingProfileId
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   */
  public function testOrder() {
    // @todo Test the store and owner getters/setters as well.
    $profile = Profile::create([
      'type' => 'billing',
      'address' => [
        'country' => 'FR',
        'postal_code' => '75002',
        'locality' => 'Paris',
        'address_line1' => 'A french street',
        'recipient' => 'John LeSmith',
      ],
    ]);
    $profile->save();
    $line_item = LineItem::create([
      'type' => 'test',
    ]);
    $line_item->save();
    $order = Order::create([
      'type' => 'default',
      'state' => 'completed',
      'order_number' => '6',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'billing_profile' => $profile,
      'line_items' => [$line_item],
    ]);
    $order->save();

    // getState() returns a StateItem.
    $this->assertEquals('completed', $order->getState()->value);

    $this->assertEquals('6', $order->getOrderNumber());
    $order->setOrderNumber(7);
    $this->assertEquals('7', $order->getOrderNumber());

    $this->assertEquals('test@example.com', $order->getEmail());
    $order->setEmail('commerce@example.com');
    $this->assertEquals('commerce@example.com', $order->getEmail());

    $this->assertEquals('127.0.0.1', $order->getIpAddress());
    $order->setIpAddress('127.0.0.2');
    $this->assertEquals('127.0.0.2', $order->getIpAddress());

    // Avoid passing an entire entity to assertEquals(), causes a crash.
    $profiles_match = $profile === $order->getBillingProfile();
    $this->assertTrue($profiles_match);
    $this->assertEquals($profile->id(), $order->getBillingProfileId());
    $another_profile = Profile::create([
      'type' => 'billing',
      'address' => [
        'country' => 'FR',
        'postal_code' => '75003',
        'locality' => 'Paris',
        'address_line1' => 'A different french street',
        'recipient' => 'Pierre Bertrand',
      ],
    ]);
    $another_profile->save();
    $order->setBillingProfileId($another_profile->id());
    $this->assertEquals($another_profile->id(), $order->getBillingProfileId());
    $order->setBillingProfile($profile);
    $this->assertEquals($profile->id(), $order->getBillingProfileId());

    // An initially saved line item won't be the same as the loaded one.
    $line_item = LineItem::load($line_item->id());
    $line_items_match = [$line_item] == $order->getLineItems();
    $this->assertTrue($line_items_match);
    $this->assertTrue($order->hasLineItem($line_item));
    $order->removeLineItem($line_item);
    $this->assertFalse($order->hasLineItems());
    $this->assertFalse($order->hasLineItem($line_item));
    $order->addLineItem($line_item);
    $this->assertTrue($order->hasLineItem($line_item));
    $another_line_item = LineItem::create([
      'type' => 'test',
    ]);
    $another_line_item->save();
    $another_line_item = LineItem::load($another_line_item->id());
    $new_line_items = [$line_item, $another_line_item];
    $order->setLineItems($new_line_items);
    $line_items_match = $new_line_items == $order->getLineItems();
    $this->assertTrue($line_items_match);
    $this->assertTrue($order->hasLineItems());

    $order->setCreatedTime('635879700');
    $this->assertEquals('635879700', $order->getCreatedTime('635879700'));
  }

}
