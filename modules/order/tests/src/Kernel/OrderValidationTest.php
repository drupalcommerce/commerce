<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests order level validation constraints.
 *
 * @group commerce
 */
class OrderValidationTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'path',
    'profile',
    'state_machine',
    'commerce_order',
  ];

  /**
   * A test user to be used as orders customer.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installConfig(['commerce_order']);
    $this->user = $this->createUser(['mail' => 'test@example.com']);
  }

  /**
   * Tests the order validation.
   */
  public function testOrderValidation() {
    // Order item needed for order validation.
    $this->entityManager->getStorage('commerce_order_item_type')->create([
      'id' => 'default',
      'label' => 'Default',
      'orderType' => 'default',
      'purchasableEntityType' => '',
    ])->save();
    $order_item = $this->entityManager->getStorage('commerce_order_item')->create([
      'type' => 'default',
    ])->save();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->entityManager->getStorage('commerce_order')->create([
      'type' => 'default',
      'mail' => $this->user->getEmail(),
      'uid' => $this->user->id(),
      'store_id' => $this->store->id(),
      'order_items' => [$order_item],
      'order_number' => 1,
    ]);
    $order->save();

    // Validate the order entity created.
    $violations = $order->validate()->getEntityViolations();
    $this->assertEquals(count($violations), 0, 'No violations when validating a default order.');

    // Order store validation.
    $order->setStoreId(NULL);
    $violations = $order->validate();
    $this->assertEquals(count($violations), 1, 'Violation found when store is empty.');
    $this->assertEquals($violations[0]->getPropertyPath(), 'store_id');
    $this->assertEquals($violations[0]->getMessage(), 'This value should not be null.');
    $order->setStoreId($this->store->id());

    // Order mail validation.
    $order->setEmail('test');
    $violations = $order->validate();
    $this->assertEquals(count($violations), 1, 'Violation found when mail id not valid.');
    $this->assertEquals($violations[0]->getPropertyPath(), 'mail.0.value');
    $this->assertEquals($violations[0]->getMessage(), 'This value is not a valid email address.');
    $order->setEmail($this->user->getEmail());

    // Order number validation.
    $order->setOrderNumber(NULL);
    $violations = $order->validate();
    $this->assertEquals(count($violations), 1, 'Violation found when order number is empty.');
    $this->assertEquals($violations[0]->getPropertyPath(), 'order_number');
    $this->assertEquals($violations[0]->getMessage(), 'This value should not be null.');
    $order->setOrderNumber(1);

    // Order items validation.
    $order->setItems([]);
    $violations = $order->validate();
    $this->assertEquals(count($violations), 1, 'Violation found when order items field is empty.');
    $this->assertEquals($violations[0]->getPropertyPath(), 'order_items');
    $this->assertEquals($violations[0]->getMessage(), 'This value should not be null.');

  }

  /**
   * Tests defining order constraints via order type annotations and hooks.
   */
  public function testOrderConstraintDefinition() {
    // Test reading the annotation. There should be a single constraint,
    // the OrderRevision defined in the order module.
    // The core EntityChanged constraint should be unset (not available).
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = \Drupal::service('entity_type.manager')->getDefinition('commerce_order');
    $default_constraints = ['OrderVersion' => []];
    $this->assertEquals($default_constraints, $order_type->getConstraints());
  }

  /**
   * Tests order constraints are validated.
   */
  public function testOrderConstraintValidation() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->entityManager->getStorage('commerce_order')->create([
      'type' => 'default',
      'mail' => $this->user->getEmail(),
      'uid' => $this->user->id(),
      'store_id' => $this->store->id(),
    ]);
    $order->save();

    // Validate the order entity created.
    $violations = $order->validate()->getEntityViolations();
    $this->assertEquals(count($violations), 0, 'No violations when validating a default order.');
    if (count($violations) > 0 && !empty($violations[0])) {
      $this->assertTrue($violations[0]->getMessage() . $violations[0]->getPropertyPath());
    }
    // Save the order for version increment.
    $order->save();

    // Set the version to 1 (first version).
    $order->set('version', 1);
    $violations = $order->validate()->getEntityViolations();
    $this->assertEquals(count($violations), 1, 'Violation found when version is less the last version.');
    $this->assertEquals($violations[0]->getPropertyPath(), '');
    $this->assertEquals($violations[0]->getMessage(), 'The order has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.');
  }

}
