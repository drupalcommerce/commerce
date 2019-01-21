<?php

namespace Drupal\commerce;

use Drupal\user\UserInterface;

/**
 * Handles the assembly and dispatch of HTML emails.
 *
 * Allows a render array (with an associated #theme) to be used as the
 * message body.
 *
 * Since Drupal core doesn't support HTML emails out of the box, Commerce
 * assumes that Swiftmailer (or an appropriate alternative) is used.
 */
interface MailHandlerInterface {

  /**
   * Sends an email to a user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   * @param string $subject
   *   The subject. Must not contain any newline characters.
   * @param array $body
   *   A render array representing the message body.
   * @param array $params
   *   Email parameters. Recognized keys:
   *     - id: A unique identifier of the email type.
   *       Allows hook_mail_alter() implementations to identify specific emails.
   *       Defaults to "mail". Automatically prefixed with "commerce_".
   *     - to: The address the email will be sent to.
   *       Must comply with RFC 2822. Defaults to the user's email address.
   *       Required if the user is anonymous.
   *     - from: The address the email will be marked as being from.
   *       Defaults to the default store email.
   *     - bcc: The BCC address or addresses. No default value.
   *
   * @return bool
   *   TRUE if the email sent successfully, FALSE otherwise.
   */
  public function sendEmail(UserInterface $account, $subject, array $body, array $params = []);

}
