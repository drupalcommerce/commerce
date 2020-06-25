<?php

namespace Drupal\Tests\commerce\Unit;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce\PurchasableEntityTypeRepository;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce\PurchasableEntityTypeRepository
 * @group commerce
 */
class PurchasableEntityTypeRepositoryTest extends UnitTestCase {

  /**
   * @covers ::getPurchasableEntityTypes
   */
  public function testGetPurchasableEntityTypes() {
    $node_entity_type = $this->prophesize(EntityTypeInterface::class);
    $node_entity_type->entityClassImplements(PurchasableEntityInterface::class)->willReturn(FALSE);
    $product_entity_type = $this->prophesize(EntityTypeInterface::class);
    $product_entity_type->entityClassImplements(PurchasableEntityInterface::class)->willReturn(FALSE);
    $product_variation_entity_type = $this->prophesize(EntityTypeInterface::class);
    $product_variation_entity_type->entityClassImplements(PurchasableEntityInterface::class)->willReturn(TRUE);

    $etm = $this->prophesize(EntityTypeManagerInterface::class);
    $etm->getDefinitions()->willReturn($this->createMockedDefinitions([
      'node' => [
        'is_purchasable' => FALSE,
        'label' => 'Node',
      ],
      'commerce_product' => [
        'is_purchasable' => FALSE,
        'label' => 'Product',
      ],
      'commerce_product_variation' => [
        'is_purchasable' => TRUE,
        'label' => 'Product variation',
      ],
    ]));

    $sut = new PurchasableEntityTypeRepository($etm->reveal());
    $purchasable_entity_types = $sut->getPurchasableEntityTypes();
    $this->assertCount(1, $purchasable_entity_types);
    $this->assertEquals(['commerce_product_variation'], array_keys($purchasable_entity_types));

    $widget_entity_type = $this->prophesize(EntityTypeInterface::class);
    $widget_entity_type->entityClassImplements(PurchasableEntityInterface::class)->willReturn(TRUE);
    $etm->getDefinitions()->willReturn($this->createMockedDefinitions([
      'node' => [
        'is_purchasable' => FALSE,
        'label' => 'Node',
      ],
      'commerce_product' => [
        'is_purchasable' => FALSE,
        'label' => 'Product',
      ],
      'commerce_product_variation' => [
        'is_purchasable' => TRUE,
        'label' => 'Product variation',
      ],
      'widget' => [
        'is_purchasable' => TRUE,
        'label' => 'Widget',
      ],
    ]));
    $purchasable_entity_types = $sut->getPurchasableEntityTypes();
    $this->assertCount(2, $purchasable_entity_types);
    $this->assertEquals(['commerce_product_variation', 'widget'], array_keys($purchasable_entity_types));
  }

  /**
   * @covers ::getPurchasableEntityTypeLabels
   */
  public function testGetPurchasableEntityTypeLabels() {
    $etm = $this->prophesize(EntityTypeManagerInterface::class);
    $etm->getDefinitions()->willReturn($this->createMockedDefinitions([
      'node' => [
        'is_purchasable' => FALSE,
        'label' => 'Node',
      ],
      'commerce_product' => [
        'is_purchasable' => FALSE,
        'label' => 'Product',
      ],
      'commerce_product_variation' => [
        'is_purchasable' => TRUE,
        'label' => 'Product variation',
      ],
      'widget' => [
        'is_purchasable' => TRUE,
        'label' => 'Widget',
      ],
    ]));

    $sut = new PurchasableEntityTypeRepository($etm->reveal());
    $this->assertEquals([
      'commerce_product_variation' => 'Product variation',
      'widget' => 'Widget',
    ], $sut->getPurchasableEntityTypeLabels());
  }

  /**
   * @covers ::getDefaultPurchasableEntityType
   */
  public function testGetDefaultPurchasableEntityType() {
    $etm = $this->prophesize(EntityTypeManagerInterface::class);
    $etm->getDefinitions()->willReturn($this->createMockedDefinitions([
      'node' => [
        'is_purchasable' => FALSE,
        'label' => 'Node',
      ],
      'commerce_product' => [
        'is_purchasable' => FALSE,
        'label' => 'Product',
      ],
      'commerce_product_variation' => [
        'is_purchasable' => TRUE,
        'label' => 'Product variation',
      ],
      'widget' => [
        'is_purchasable' => TRUE,
        'label' => 'Widget',
      ],
    ]));

    $sut = new PurchasableEntityTypeRepository($etm->reveal());
    $default = $sut->getDefaultPurchasableEntityType();
    $this->assertEquals($default->getLabel(), 'Product variation');

    $etm->getDefinitions()->willReturn($this->createMockedDefinitions([
      'node' => [
        'is_purchasable' => FALSE,
        'label' => 'Node',
      ],
      'widget' => [
        'is_purchasable' => TRUE,
        'label' => 'Widget',
      ],
    ]));
    $sut = new PurchasableEntityTypeRepository($etm->reveal());
    $default = $sut->getDefaultPurchasableEntityType();
    $this->assertEquals($default->getLabel(), 'Widget');
  }

  /**
   * Creates mocked entity type definitions.
   *
   * @param array $definition_items
   *   The definition items.
   *
   * @return array
   *   The mocked definitions.
   */
  private function createMockedDefinitions(array $definition_items): array {
    $definitions = [];
    foreach ($definition_items as $entity_type_id => $data) {
      $mock = $this->prophesize(EntityTypeInterface::class);
      $mock->entityClassImplements(PurchasableEntityInterface::class)->willReturn($data['is_purchasable']);
      $mock->getLabel()->willReturn($data['label']);
      $definitions[$entity_type_id] = $mock->reveal();
    }
    return $definitions;
  }

}
