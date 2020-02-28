<?php

namespace Drupal\Tests\commerce\FunctionalJavascript;

use Drupal\commerce_price\Comparator\NumberComparator;
use Drupal\commerce_price\Comparator\PriceComparator;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\commerce\Traits\CommerceBrowserTestTrait;
use Drupal\Tests\commerce\Traits\DeprecationSuppressionTrait;
use SebastianBergmann\Comparator\Factory as PhpUnitComparatorFactory;

/**
 * Provides a base class for Commerce functional tests.
 */
abstract class CommerceWebDriverTestBase extends WebDriverTestBase {

  use BlockCreationTrait;
  use StoreCreationTrait;
  use CommerceBrowserTestTrait;
  use DeprecationSuppressionTrait;

  /**
   * The store entity.
   *
   * @var \Drupal\commerce_store\Entity\Store
   */
  protected $store;

  /**
   * The country list.
   *
   * @var array
   */
  protected $countryList;

  /**
   * Modules to enable.
   *
   * Note that when a child class declares its own $modules list, that list
   * doesn't override this one, it just extends it.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'block',
    'field',
    'commerce',
    'commerce_price',
    'commerce_store',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->setErrorHandler();
    parent::setUp();

    $factory = PhpUnitComparatorFactory::getInstance();
    $factory->register(new NumberComparator());
    $factory->register(new PriceComparator());

    $this->store = $this->createStore();
    $this->placeBlock('local_tasks_block');
    $this->placeBlock('local_actions_block');
    $this->placeBlock('page_title_block');

    $country_repository = $this->container->get('address.country_repository');
    $this->countryList = $country_repository->getList();

    $this->adminUser = $this->drupalCreateUser($this->getAdministratorPermissions());
    $this->drupalLogin($this->adminUser);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();
    $this->restoreErrorHandler();
  }

  /**
   * Gets the permissions for the admin user.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getAdministratorPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'access commerce administration pages',
      'administer commerce_currency',
      'administer commerce_store',
      'administer commerce_store_type',
    ];
  }

  /**
   * Sets an input field's raw value.
   *
   * HTML5 date and time input elements use locale-specific formats,
   * making it difficult to test across environments.
   * Setting the value via JS allows us to bypass this and modify the underling
   * value, which is always in a consistent format.
   *
   * @param string $name
   *   The input element name.
   * @param string $value
   *   The value.
   */
  protected function setRawFieldValue($name, $value) {
    $this->getSession()->executeScript("document.getElementsByName('{$name}')[0].value = '{$value}';");
  }

  /**
   * Asserts that the given address is rendered on the page.
   *
   * @param array $address
   *   The address.
   * @param string $container
   *   The name of the containing profile element. Defaults to 'profile'.
   */
  protected function assertRenderedAddress(array $address, $container = 'profile') {
    $page = $this->getSession()->getPage();
    $address_text = $page->find('css', 'p.address')->getText();
    foreach ($address as $property => $value) {
      if ($property == 'country_code') {
        $value = $this->countryList[$value];
      }
      // Can't use assertContains() while maintaining D9 compatibility.
      $this->assertTrue(strpos($address_text, $value) !== FALSE);
      $this->assertSession()->fieldNotExists($container . "[address][0][address][$property]");
    }
    $this->assertSession()->fieldNotExists($container . '[copy_to_address_book]');
  }

  /**
   * Waits for the given time or until the given JS condition becomes TRUE.
   *
   * @param string $condition
   *   JS condition to wait until it becomes TRUE.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 1000.
   * @param string $message
   *   (optional) A message to display with the assertion. If left blank, a
   *   default message will be displayed.
   *
   * @see \Behat\Mink\Driver\DriverInterface::evaluateScript()
   */
  protected function assertJsCondition($condition, $timeout = 1000, $message = '') {
    $message = $message ?: "Javascript condition met:\n" . $condition;
    $result = $this->getSession()->getDriver()->wait($timeout, $condition);
    $this->assertNotEmpty($result, $message);
  }

  /**
   * Waits for jQuery to become active and animations to complete.
   *
   * @deprecated in commerce:8.x-2.16 and is removed from commerce:3.x.
   */
  protected function waitForAjaxToFinish() {
    $condition = "(0 === jQuery.active && 0 === jQuery(':animated').length)";
    $this->assertJsCondition($condition, 10000);
  }

}
