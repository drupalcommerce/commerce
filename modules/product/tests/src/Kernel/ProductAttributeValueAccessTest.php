<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the product attribute value access control.
 *
 * @coversDefaultClass \Drupal\commerce_product\ProductAttributeValueAccessControlHandler
 * @group commerce
 */
class ProductAttributeValueAccessTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_attribute');
    $this->installEntitySchema('commerce_product_attribute_value');

    // Create uid: 1 here so that it's skipped in test cases.
    $admin_user = $this->createUser();
  }

  /**
   * @covers ::checkAccess
   */
  public function testAccess() {
    /** @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface $attribute */
    $attribute = ProductAttribute::create([
      'id' => 'color',
      'label' => 'Color',
    ]);
    $attribute->save();
    /** @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface $attribute_value */
    $attribute_value = ProductAttributeValue::create([
      'attribute' => 'color',
      'name' => 'Black',
      'weight' => 3,
    ]);
    $attribute_value->save();

    $account = $this->createUser([], ['access administration pages']);
    $this->assertFalse($attribute_value->access('view', $account));
    $this->assertFalse($attribute_value->access('update', $account));
    $this->assertFalse($attribute_value->access('delete', $account));

    $account = $this->createUser([], ['access content']);
    $this->assertTrue($attribute_value->access('view', $account));
    $this->assertFalse($attribute_value->access('update', $account));
    $this->assertFalse($attribute_value->access('delete', $account));

    $account = $this->createUser([], ['update commerce_product_attribute']);
    $this->assertFalse($attribute_value->access('view', $account));
    $this->assertTrue($attribute_value->access('update', $account));
    $this->assertTrue($attribute_value->access('delete', $account));

    $account = $this->createUser([], ['administer commerce_product_attribute']);
    $this->assertTrue($attribute_value->access('view', $account));
    $this->assertTrue($attribute_value->access('update', $account));
    $this->assertTrue($attribute_value->access('delete', $account));
  }

  /**
   * @covers ::checkCreateAccess
   */
  public function testCreateAccess() {
    $access_control_handler = \Drupal::entityTypeManager()->getAccessControlHandler('commerce_product_attribute_value');
    /** @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface $attribute */
    $attribute = ProductAttribute::create([
      'id' => 'color',
      'label' => 'Color',
    ]);
    $attribute->save();

    $account = $this->createUser([], ['access content']);
    $this->assertFalse($access_control_handler->createAccess('color', $account));

    $account = $this->createUser([], ['administer commerce_product_attribute']);
    $this->assertTrue($access_control_handler->createAccess('color', $account));

    $account = $this->createUser([], ['update commerce_product_attribute']);
    $this->assertTrue($access_control_handler->createAccess('color', $account));
  }

}
