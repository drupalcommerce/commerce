<?php

namespace Drupal\commerce;

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
   * @param string $to
   *   The address the email will be sent to. Must comply with RFC 2822.
   * @param string $subject
   *   The subject. Must not contain any newline characters.
   * @param array $body
   *   A render array representing the message body.
   * @param array $params
   *   Email parameters. Recognized keys:
   *     - id: A unique identifier of the email type.
   *       Allows hook_mail_alter() implementations to identify specific emails.
   *       Defaults to "mail". Automatically prefixed with "commerce_".
   *     - from: The address the email will be marked as being from.
   *       Defaults to the current store email.
   *     - bcc: The BCC address or addresses (separated by a comma). No default.
   *     - langcode: The email langcode. Every translatable string and entity
   *       will be rendered in this language. Defaults to the current language.
   *
   * @return bool
   *   TRUE if the email was sent successfully, FALSE otherwise.
   */
  public function sendMail($to, $subject, array $body, array $params = []);

}
