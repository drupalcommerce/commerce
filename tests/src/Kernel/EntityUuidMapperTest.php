<?php

namespace Drupal\Tests\commerce\Kernel;

/**
 * Tests the EntityUuidMapper class.
 *
 * @coversDefaultClass \Drupal\commerce\EntityUuidMapper
 * @group commerce
 */
class EntityUuidMapperTest extends CommerceKernelTestBase {

  /**
   * The entity UUID mapper.
   *
   * @var \Drupal\commerce\EntityUuidMapperInterface
   */
  protected $entityUuidMapper;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityUuidMapper = $this->container->get('commerce.entity_uuid_mapper');
  }

  /**
   * Tests the mapper.
   *
   * @covers ::mapToIds
   * @covers ::mapFromIds
   */
  public function testMapper() {
    $another_store = $this->createStore('Second store', 'second@example.com');
    $map = [
      $this->store->id() => $this->store->uuid(),
      $another_store->id() => $another_store->uuid(),
    ];

    $uuid_map = $this->entityUuidMapper->mapToIds('commerce_store', array_values($map));
    $this->assertEquals($uuid_map, array_flip($map));

    $id_map = $this->entityUuidMapper->mapFromIds('commerce_store', array_keys($map));
    $this->assertEquals($id_map, $map);
  }

}
