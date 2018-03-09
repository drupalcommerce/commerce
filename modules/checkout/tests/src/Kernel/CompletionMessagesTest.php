<?php

namespace Drupal\Tests\commerce_checkout\Kernel;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CompletionMessages;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the completion messages class.
 *
 * @covers \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CompletionMessages
 *
 * @group commerce
 */
class CompletionMessagesTest extends CommerceKernelTestBase {

  /**
   * @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CompletionMessages
   */
  private $completionMessages;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->completionMessages = new CompletionMessages();
  }

  /**
   * Tests add message method.
   */
  public function testAddMessage() {
    $this->completionMessages->addMessage(t('Message 1'));
    $this->completionMessages->addMessage(t('Message 2'));

    $this->assertCount(2, $this->completionMessages);
  }

  /**
   * Tests the messages iterator.
   */
  public function testMessagesIterator() {
    $this->completionMessages->addMessage(t('Message 1'));
    $this->completionMessages->addMessage(t('Message 2'));

    $this->assertEquals('Message 1', $this->completionMessages->current()->render());
    $this->completionMessages->next();
    $this->assertEquals('Message 2', $this->completionMessages->current()->render());
  }

}
