<?php

namespace Drupal\Tests\commerce\Kernel;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\user\Entity\User;

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
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->user = $this->createUser(['mail' => 'customer@example.com']);
    $this->mailHandler = $this->container->get('commerce.mail_handler');
  }

  /**
   * Tests sending a basic email, without any custom parameters.
   */
  public function testBasicEmail() {
    $this->assertTrue($this->user->isAuthenticated());
    $body = [
      '#markup' => '<p>' . $this->t('Mail Handler Test') . '</p>',
    ];
    $result = $this->mailHandler->sendEmail($this->user, 'Test subject', $body);
    $this->assertTrue($result);

    $emails = $this->getMails();
    $this->assertEquals(1, count($emails));
    $email = reset($emails);
    $this->assertEquals('text/html; charset=UTF-8;', $email['headers']['Content-Type']);
    $this->assertEquals('commerce_mail', $email['id']);
    $this->assertEquals($this->user->getEmail(), $email['to']);
    $this->assertFalse(isset($email['headers']['Bcc']));
    $this->assertEquals($this->store->getEmail(), $email['from']);
    $this->assertEquals('Test subject', $email['subject']);
    $this->assertContains('Mail Handler Test', $email['body']);

    // No email should be sent if the authenticated user has no email specified.
    $another_user = $this->createUser();
    $result = $this->mailHandler->sendEmail($another_user, 'Test subject', $body);
    $this->assertFalse($result);

    // An exception should be thrown when trying to email an anonymous user
    // without a "to" parameter.
    $exception_thrown = FALSE;
    try {
      $this->mailHandler->sendEmail(User::getAnonymousUser(), 'Test subject', $body);
    }
    catch (\InvalidArgumentException $e) {
      $exception_thrown = TRUE;
    }
    $this->assertTrue($exception_thrown);
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
      'to' => 'you@example.com',
      'from' => 'me@example.com',
      'bcc' => 'other@example.com',
      'uid' => $this->user->id(),
    ];
    // Test with both an authenticated and an anonymous user, to confirm that
    // the "to" parameter is used in both cases.
    $users = [$this->user, User::getAnonymousUser()];
    foreach ($users as $user) {
      $result = $this->mailHandler->sendEmail($user, 'Hello #' . $user->id(), $body, $params);
      $this->assertTrue($result);

      $emails = $this->getMails();
      $email = end($emails);
      $this->assertEquals('commerce_custom', $email['id']);
      $this->assertEquals('you@example.com', $email['to']);
      $this->assertEquals('other@example.com', $email['headers']['Bcc']);
      $this->assertEquals('me@example.com', $email['from']);
      $this->assertEquals('Hello #' . $user->id(), $email['subject']);
      $this->assertContains('Custom Mail Handler Test', $email['body']);
      $this->assertEquals($this->user->id(), $email['params']['uid']);
    }
  }

}
