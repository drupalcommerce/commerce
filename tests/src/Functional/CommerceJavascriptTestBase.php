<?php

namespace Drupal\Tests\commerce\Functional;

use Zumba\Mink\Driver\PhantomJSDriver;

/**
 * Provides a base class for Commerce functional tests with JavaScript.
 *
 * This class does not extend JavascriptTestBase. It uses the same initMink
 * override code to tell Mink to use PhantomJS.
 *
 * This allows us to re-use our helper methods in CommerceBrowserTestBase.
 */
abstract class CommerceJavascriptTestBase extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $minkDefaultDriverClass = PhantomJSDriver::class;

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\FunctionalJavascriptTests\JavascriptTestBase::initMink
   */
  protected function initMink() {
    // Set up the template cache used by the PhantomJS mink driver.
    $path = $this->tempFilesDirectory . DIRECTORY_SEPARATOR . 'browsertestbase-templatecache';
    $this->minkDefaultDriverArgs = [
      'http://127.0.0.1:8510',
      $path,
    ];
    if (!file_exists($path)) {
      mkdir($path);
    }
    return parent::initMink();
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
    $this->assertTrue($result, $message);
  }

  /**
   * Waits for jQuery to become active and animations to complete.
   */
  protected function waitForAjaxToFinish() {
    $condition = "(0 === jQuery.active && 0 === jQuery(':animated').length)";
    $this->assertJsCondition($condition, 10000);
  }

}
