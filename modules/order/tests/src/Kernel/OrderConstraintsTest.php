<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
/**
 * Tests order level validation constraints.
 *
 * @group commerce
 */
class OrderConstraintsTest extends CommerceKernelTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installConfig(['commerce_order']);
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
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->entityManager->getStorage('commerce_order')->create([
      'type' => 'default',
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
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
