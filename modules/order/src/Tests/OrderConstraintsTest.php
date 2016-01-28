<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Tests\OrderConstraintsTest.
 */

namespace Drupal\commerce_order\Tests;

use Drupal\commerce_order\Tests\OrderTestBase;

/**
 * Tests order level validation constraints.
 *
 * @group commerce
 */
class OrderConstraintsTest extends OrderTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests defining order constraints via order type annotations and hooks.
   */
  public function testOrderConstraintDefinition() {
    // Test reading the annotation. There should be a single constraint,
    // the OrderRevision defined in the order module.
    // The core EntityChanged constraint should be unset (not available).
    $order_type = \Drupal::service('entity_type.manager')->getDefinition('commerce_order');
    $default_constraints = ['OrderVersion' => []];
    $this->assertEqual($default_constraints, $order_type->getConstraints());
  }

  /**
   * Tests order constraints are validated.
   */
  public function testOrderConstraintValidation() {
    $line_item = $this->createEntity('commerce_line_item', [
      'type' => 'product_variation',
    ]);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'line_items' => [$line_item],
      'store_id' => $this->store->id(),
    ]);

    // Validate the order created.
    $violations = $order->validate();
    $this->assertEqual(count($violations), 0, 'No violations when validating a default order.');
    if (count($violations) > 0 && !empty($violations[0])) {
      $this->pass($violations[0]->getMessage() . $violations[0]->getPropertyPath());
    }

    // Save the order for version increment.
    $order->save();

    // Set the version to 1 (first version).
    $order->set('version', 1);
    $violations = $order->validate();
    $this->assertEqual(count($violations), 1, 'Violation found when version is less the last version.');
    $this->assertEqual($violations[0]->getPropertyPath(), '');
    $this->assertEqual($violations[0]->getMessage(), 'The order has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.');
  }

}
