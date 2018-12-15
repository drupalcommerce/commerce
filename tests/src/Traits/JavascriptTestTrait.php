<?php

namespace Drupal\Tests\commerce\Traits;

@trigger_error('The ' . __NAMESPACE__ . '\JavascriptTestTrait is deprecated in Commerce 2.x and will be removed before Commerce 3.0.0. Instead, use ' . __NAMESPACE__ . '\CommerceWebDriverTestBase. See https://www.drupal.org/project/commerce/issues/2998745', E_USER_DEPRECATED);

use Drupal\FunctionalJavascriptTests\JSWebAssert;
use Zumba\Mink\Driver\PhantomJSDriver;

/**
 * Allows tests using BrowserTest run with Javascript enabled.
 */
trait JavascriptTestTrait {

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\FunctionalJavascriptTests\JavascriptTestBase::initMink
   */
  protected function initMink() {
    $this->minkDefaultDriverClass = PhantomJSDriver::class;
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
    $this->assertNotEmpty($result, $message);
  }

  /**
   * Waits for jQuery to become active and animations to complete.
   */
  protected function waitForAjaxToFinish() {
    $condition = "(0 === jQuery.active && 0 === jQuery(':animated').length)";
    $this->assertJsCondition($condition, 10000);
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\FunctionalJavascriptTests\JSWebAssert
   *   A new web-assert option for asserting the presence of elements with.
   */
  public function assertSession($name = NULL) {
    return new JSWebAssert($this->getSession($name), $this->baseUrl);
  }

  /**
   * Creates a screenshot.
   *
   * @param bool $set_background_color
   *   (optional) By default this method will set the background color to white.
   *   Set to FALSE to override this behaviour.
   *
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   *   When operation not supported by the driver.
   * @throws \Behat\Mink\Exception\DriverException
   *   When the operation cannot be done.
   */
  protected function createScreenshot($set_background_color = TRUE) {
    $jpg_output_filename = $this->htmlOutputClassName . '-' . $this->htmlOutputCounter . '-' . $this->htmlOutputTestId . '.jpg';
    $session = $this->getSession();
    if ($set_background_color) {
      $session->executeScript("document.body.style.backgroundColor = 'white';");
    }
    $image = $session->getScreenshot();
    file_put_contents($this->htmlOutputDirectory . '/' . $jpg_output_filename, $image);
    $this->htmlOutputCounter++;
  }

}
