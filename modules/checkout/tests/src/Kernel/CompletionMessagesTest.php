<?php
/**
 * Created by PhpStorm.
 * User: robharings
 * Date: 09/09/2017
 * Time: 13:31
 */

namespace Drupal\Tests\commerce_checkout\Kernel;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CompletionMessages;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the completion messages class.
 *
 * @covers \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CompletionMessages
 */
class CompletionMessagesTest extends CommerceKernelTestBase {

  /**
   * @var CompletionMessages
   */
  private $completionMessages;

  public function setUp() {
    parent::setUp();
    $this->completionMessages = new CompletionMessages();
  }

  public function testAddMessage() {
    $this->completionMessages->addMessage(t('Message 1'));
    $this->completionMessages->addMessage(t('Message 2'));

    $this->assertCount(2, $this->completionMessages);
  }

  public function testMessagesIterator() {
    $this->completionMessages->addMessage(t('Message 1'));
    $this->completionMessages->addMessage(t('Message 2'));

    $this->assertEquals('Message 1', $this->completionMessages->current()->render());
    $this->completionMessages->next();
    $this->assertEquals('Message 2', $this->completionMessages->current()->render());
  }

}