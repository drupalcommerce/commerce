<?php

namespace Drupal\Tests\commerce_price\Unit;

use Drupal\commerce_price\Entity\CurrencyInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\Rounder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Rounder class.
 *
 * @coversDefaultClass \Drupal\commerce_price\Rounder
 * @group commerce
 */
class RounderTest extends UnitTestCase {

  /**
   * The rounder.
   *
   * @var \Drupal\commerce_price\Rounder
   */
  protected $rounder;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $usd_currency = $this->prophesize(CurrencyInterface::class);
    $usd_currency->id()->willReturn('USD');
    $usd_currency->getFractionDigits()->willReturn('2');

    $storage = $this->prophesize(EntityStorageInterface::class);
    $storage->load('USD')->willReturn($usd_currency->reveal());
    $storage->load('EUR')->willReturn(NULL);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('commerce_currency')->willReturn($storage->reveal());

    $this->rounder = new Rounder($entity_type_manager->reveal());
  }

  /**
   * Tests rounding a price with an unknown currency.
   *
   * ::covers round.
   */
  public function testUnknownCurrency() {
    $this->setExpectedException(\InvalidArgumentException::class);
    $this->rounder->round(new Price('10', 'EUR'));
  }

  /**
   * Tests rounding a valid price.
   *
   * ::covers round.
   */
  public function testValid() {
    $rounded_price = $this->rounder->round(new Price('3.3698', 'USD'));
    $this->assertEquals('3.37', $rounded_price->getNumber());
    $this->assertEquals('USD', $rounded_price->getCurrencyCode());
  }

}
