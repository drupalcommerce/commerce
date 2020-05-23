<?php

namespace Drupal\Tests\commerce\Kernel;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Test\AssertMailTrait;

/**
 * Tests the sending of customer emails.
 *
 * @group commerce
 */
class MailHandlerTest extends CommerceKernelTestBase {

  use AssertMailTrait;
  use StringTranslationTrait;

  /**
   * The mail handler.
   *
   * @var \Drupal\commerce\MailHandlerInterface
   */
  protected $mailHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->mailHandler = $this->container->get('commerce.mail_handler');
  }

  /**
   * Tests sending a basic email, without any custom parameters.
   */
  public function testBasicEmail() {
    $body = [
      '#markup' => '<p>' . $this->t('Mail Handler Test') . '</p>',
    ];
    $result = $this->mailHandler->sendMail('customer@example.com', 'Test subject', $body);
    $this->assertTrue($result);

    $language_manager = $this->container->get('language_manager');
    $emails = $this->getMails();
    $this->assertEquals(1, count($emails));
    $email = reset($emails);
    $this->assertEquals('text/html; charset=UTF-8;', $email['headers']['Content-Type']);
    $this->assertEquals('commerce_mail', $email['id']);
    $this->assertEquals('customer@example.com', $email['to']);
    $this->assertFalse(isset($email['headers']['Cc']));
    $this->assertFalse(isset($email['headers']['Bcc']));
    $this->assertFalse(isset($email['headers']['Reply-to']));
    $this->assertEquals($this->store->getEmail(), $email['from']);
    $this->assertEquals('Test subject', $email['subject']);
    $this->assertStringContainsString('Mail Handler Test', $email['body']);
    $this->assertEquals($language_manager->getCurrentLanguage()->getId(), $email['langcode']);

    // No email should be sent if the recipient is empty.
    $result = $this->mailHandler->sendMail('', 'Test subject', $body);
    $this->assertFalse($result);
  }

  /**
   * Tests sending an email with custom parameters.
   */
  public function testCustomEmail() {
    $body = [
      '#markup' => '<p>' . $this->t('Custom Mail Handler Test') . '</p>',
    ];
    $params = [
      'id' => 'custom',
      'from' => 'me@example.com',
      'reply-to' => 'actually.me@example.com',
      'cc' => 'billing@example.com',
      'bcc' => 'other@example.com',
      'langcode' => 'fr',
      // Custom parameters are passed through.
      'foo' => 'bar',
    ];
    $result = $this->mailHandler->sendMail('you@example.com', 'Hello', $body, $params);
    $this->assertTrue($result);

    $emails = $this->getMails();
    $email = end($emails);
    $this->assertEquals('commerce_custom', $email['id']);
    $this->assertEquals('you@example.com', $email['to']);
    $this->assertEquals('billing@example.com', $email['headers']['Cc']);
    $this->assertEquals('other@example.com', $email['headers']['Bcc']);
    $this->assertEquals('actually.me@example.com', $email['headers']['Reply-to']);
    $this->assertEquals('me@example.com', $email['from']);
    $this->assertEquals('Hello', $email['subject']);
    $this->assertStringContainsString('Custom Mail Handler Test', $email['body']);
    $this->assertEquals('fr', $email['langcode']);
    $this->assertEquals('bar', $email['params']['foo']);
  }

}
