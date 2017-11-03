<?php

namespace Drupal\Tests\commerce\Kernel;

use Drupal\commerce\Interval;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Interval class.
 *
 * @coversDefaultClass \Drupal\commerce\Interval
 * @group commerce
 */
class IntervalTest extends KernelTestBase {

  /**
   * The interval.
   *
   * @var \Drupal\commerce\Interval
   */
  protected $interval;

  /**
   * Tests creating an interval with an invalid number.
   *
   * ::covers __construct.
   */
  public function testInvalidNumber() {
    $this->setExpectedException(\InvalidArgumentException::class);
    $interval = new Interval('INVALID', 'month');
  }

  /**
   * Tests creating an interval with an invalid unit.
   *
   * ::covers __construct.
   */
  public function testInvalidUnit() {
    $this->setExpectedException(\InvalidArgumentException::class);
    $interval = new Interval('1', 'INVALID');
  }

  /**
   * Tests the methods for getting the number/unit in various formats.
   *
   * ::covers getNumber
   * ::covers getUnit
   * ::covers __toString
   * ::covers toArray.
   */
  public function testGetters() {
    $interval = new Interval('1', 'month');
    $this->assertEquals('1', $interval->getNumber());
    $this->assertEquals('month', $interval->getUnit());
    $this->assertEquals('1 month', $interval->__toString());
    $this->assertEquals(['number' => '1', 'unit' => 'month'], $interval->toArray());
  }

  /**
   * @dataProvider additionDateProvider
   */
  public function testAddition($date, Interval $interval, $expected_date) {
    $date = DrupalDateTime::createFromFormat('Y-m-d H:i', $date);
    $expected_date = DrupalDateTime::createFromFormat('Y-m-d H:i', $expected_date);
    $this->assertEquals($expected_date, $interval->add($date));
  }

  /**
   * @dataProvider subtractionDateProvider
   */
  public function testSubtraction($date, Interval $interval, $expected_date) {
    $date = DrupalDateTime::createFromFormat('Y-m-d H:i', $date);
    $expected_date = DrupalDateTime::createFromFormat('Y-m-d H:i', $expected_date);
    $this->assertEquals($expected_date, $interval->subtract($date));
  }

  /**
   * @dataProvider flooringDateProvider
   */
  public function testFlooring($date, Interval $interval, $expected_date) {
    $date = DrupalDateTime::createFromFormat('Y-m-d H:i', $date);
    $expected_date = DrupalDateTime::createFromFormat('Y-m-d H:i', $expected_date);
    $this->assertEquals($expected_date, $interval->floor($date));
  }

  /**
   * @dataProvider ceilingDateProvider
   */
  public function testCeiling($date, Interval $interval, $expected_date) {
    $date = DrupalDateTime::createFromFormat('Y-m-d H:i', $date);
    $expected_date = DrupalDateTime::createFromFormat('Y-m-d H:i', $expected_date);
    $this->assertEquals($expected_date, $interval->ceil($date));
  }

  /**
   * Data provider for ::testAddition.
   *
   * @return array
   *   A list of testAddition function arguments.
   */
  public function additionDateProvider() {
    return [
      ['2017-02-24 17:15' , new Interval('1', 'hour'), '2017-02-24 18:15'],
      ['2017-02-24 17:15' , new Interval('8', 'hour'), '2017-02-25 01:15'],

      ['2017-02-24 17:15' , new Interval('1', 'day'), '2017-02-25 17:15'],
      ['2017-02-24 17:15' , new Interval('14', 'day'), '2017-03-10 17:15'],

      ['2017-02-24 17:15' , new Interval('1', 'week'), '2017-03-03 17:15'],
      ['2017-02-24 17:15' , new Interval('3', 'week'), '2017-03-17 17:15'],

      ['2017-02-24 17:15', new Interval('1', 'month'), '2017-03-24 17:15'],
      ['2017-02-24 17:15', new Interval('2', 'month'), '2017-04-24 17:15'],

      ['2017-01-31 17:15', new Interval('1', 'month'), '2017-02-28 17:15'],
      ['2017-02-28 17:15', new Interval('1', 'month'), '2017-03-28 17:15'],
      ['2017-01-31 17:15', new Interval('3', 'month'), '2017-04-30 17:15'],

      ['2017-02-24 17:15', new Interval('1', 'year'), '2018-02-24 17:15'],
      ['2017-02-24 17:15', new Interval('2', 'year'), '2019-02-24 17:15'],
    ];
  }

  /**
   * Data provider for ::testSubtraction.
   *
   * @return array
   *   A list of testSubtraction function arguments.
   */
  public function subtractionDateProvider() {
    return [
      ['2017-02-24 17:15' , new Interval('1', 'hour'), '2017-02-24 16:15'],
      ['2017-02-24 17:15' , new Interval('18', 'hour'), '2017-02-23 23:15'],

      ['2017-02-24 17:15' , new Interval('1', 'day'), '2017-02-23 17:15'],
      ['2017-02-24 17:15' , new Interval('30', 'day'), '2017-01-25 17:15'],

      ['2017-02-24 17:15' , new Interval('1', 'week'), '2017-02-17 17:15'],
      ['2017-02-24 17:15' , new Interval('4', 'week'), '2017-01-27 17:15'],

      ['2017-02-24 17:15', new Interval('1', 'month'), '2017-01-24 17:15'],
      ['2017-02-24 17:15', new Interval('2', 'month'), '2016-12-24 17:15'],

      ['2017-03-31 17:15', new Interval('1', 'month'), '2017-02-28 17:15'],
      ['2017-02-28 17:15', new Interval('1', 'month'), '2017-01-28 17:15'],
      ['2017-03-31 17:15', new Interval('4', 'month'), '2016-11-30 17:15'],

      ['2017-02-24 17:15', new Interval('1', 'year'), '2016-02-24 17:15'],
      ['2017-02-24 17:15', new Interval('2', 'year'), '2015-02-24 17:15'],
    ];
  }

  /**
   * Data provider for ::testFlooring.
   *
   * @return array
   *   A list of testFlooring function arguments.
   */
  public function flooringDateProvider() {
    return [
      ['2017-02-24 17:15' , new Interval('1', 'hour'), '2017-02-24 17:00'],
      ['2017-02-24 17:15' , new Interval('2', 'hour'), '2017-02-24 17:00'],

      ['2017-02-24 17:15' , new Interval('1', 'day'), '2017-02-24 00:00'],
      ['2017-02-24 17:15' , new Interval('2', 'day'), '2017-02-24 00:00'],

      ['2017-02-24 17:15' , new Interval('1', 'week'), '2017-02-20 00:00'],
      ['2017-02-24 17:15' , new Interval('4', 'week'), '2017-02-20 00:00'],

      ['2017-02-24 17:15', new Interval('1', 'month'), '2017-02-01 00:00'],
      ['2017-02-24 17:15', new Interval('2', 'month'), '2017-02-01 00:00'],

      ['2017-02-24 17:15', new Interval('1', 'year'), '2017-01-01 00:00'],
      ['2017-02-24 17:15', new Interval('2', 'year'), '2017-01-01 00:00'],
    ];
  }

  /**
   * Data provider for ::testCeiling.
   *
   * @return array
   *   A list of testCeiling function arguments.
   */
  public function ceilingDateProvider() {
    return [
      ['2017-02-24 17:15' , new Interval('1', 'hour'), '2017-02-24 18:00'],
      ['2017-02-24 17:15' , new Interval('2', 'hour'), '2017-02-24 19:00'],

      ['2017-02-24 17:15' , new Interval('1', 'day'), '2017-02-25 00:00'],
      ['2017-02-24 17:15' , new Interval('2', 'day'), '2017-02-26 00:00'],

      ['2017-02-24 17:15' , new Interval('1', 'week'), '2017-02-27 00:00'],
      ['2017-02-24 17:15' , new Interval('4', 'week'), '2017-03-20 00:00'],

      ['2017-02-24 17:15', new Interval('1', 'month'), '2017-03-01 00:00'],
      ['2017-02-24 17:15', new Interval('2', 'month'), '2017-04-01 00:00'],

      ['2017-02-24 17:15', new Interval('1', 'year'), '2018-01-01 00:00'],
      ['2017-02-24 17:15', new Interval('2', 'year'), '2019-01-01 00:00'],
    ];
  }

}
