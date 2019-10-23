<?php

namespace Drupal\Tests\commerce\Kernel;

use Drupal\Core\Test\AssertMailTrait;

/**
 * Tests sending emails using the MailSystem mail theme setting.
 *
 * @requires module mailsystem
 * @group commerce
 */
class MailHandlerThemeTest extends CommerceKernelTestBase {

  use AssertMailTrait;

  /**
   * The mail handler.
   *
   * @var \Drupal\commerce\MailHandlerInterface
   */
  protected $mailHandler;

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'mailsystem',
    'mailsystem_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['mailsystem']);
    \Drupal::service('theme_installer')->install(['commerce_test_theme']);

    $this->mailHandler = $this->container->get('commerce.mail_handler');
    $this->user = $this->createUser(['mail' => 'customer@example.com']);
  }

  /**
   * Tests the email without a custom theme.
   */
  public function testDefaultTheme() {
    $mailsystem_config = $this->config('mailsystem.settings');
    $mailsystem_config
      ->set('defaults.sender', 'test_mail_collector')
      ->set('defaults.formatter', 'test_mail_collector')
      ->save();

    $body = [
      '#theme' => 'username',
      '#account' => $this->user,
    ];
    $this->mailHandler->sendMail($this->user->getEmail(), 'Hello, customer!', $body);

    $emails = $this->getMails();
    $this->assertEquals(1, count($emails));
    $email = reset($emails);
    $this->assertEquals('text/html; charset=UTF-8;', $email['headers']['Content-Type']);
    $this->assertEquals('commerce_mail', $email['id']);
    $this->assertEquals($this->user->getEmail(), $email['to']);
    $this->assertFalse(isset($email['headers']['Bcc']));
    $this->assertEquals($this->store->getEmail(), $email['from']);
    $this->assertEquals('Hello, customer!', $email['subject']);
    $this->assertNotContains('Commerce test theme', $email['body']);
  }

  /**
   * Tests the email with custom theme.
   */
  public function testCustomTheme() {
    $mailsystem_config = $this->config('mailsystem.settings');
    $mailsystem_config
      ->set('defaults.sender', 'test_mail_collector')
      ->set('defaults.formatter', 'test_mail_collector')
      ->set('theme', 'commerce_test_theme')
      ->save();

    $body = [
      '#theme' => 'username',
      '#account' => $this->user,
    ];
    $this->mailHandler->sendMail($this->user->getEmail(), 'Hello, customer!', $body);

    $emails = $this->getMails();
    $this->assertEquals(1, count($emails));
    $email = reset($emails);
    $this->assertEquals('text/html; charset=UTF-8;', $email['headers']['Content-Type']);
    $this->assertEquals('commerce_mail', $email['id']);
    $this->assertEquals($this->user->getEmail(), $email['to']);
    $this->assertFalse(isset($email['headers']['Bcc']));
    $this->assertEquals($this->store->getEmail(), $email['from']);
    $this->assertEquals('Hello, customer!', $email['subject']);
    $this->assertContains('Commerce test theme', $email['body']);
  }

}
