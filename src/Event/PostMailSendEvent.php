<?php

namespace Drupal\commerce\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Provides the event dispatched after sending emails via the mail handler.
 *
 * Note that there's no matching PreMailSendEvent as this is already covered
 * by the hook_mail_alter().
 *
 * @see \Drupal\commerce\Event\CommerceEvents
 */
class PostMailSendEvent extends Event {

  /**
   * The email parameters.
   *
   * @var array
   */
  protected $params;

  /**
   * The message array.
   *
   * @var array
   */
  protected $message;

  /**
   * Constructs a new PostMailSendEvent object.
   *
   * @param array $params
   *   An array of emails parameters.
   * @param array $message
   *   The $message array structure containing all details of the message. If
   *   already sent ($send = TRUE), then the 'result' element will contain the
   *   success indicator of the email, failure being already written to the
   *   watchdog.
   */
  public function __construct(array $params, array $message) {
    $this->params = $params;
    $this->message = $message;
  }

  /**
   * Gets the email parameters.
   *
   * @return array
   *   An array of emails parameters.
   */
  public function getParams() {
    return $this->params;
  }

  /**
   * Gets the message array.
   *
   * @return array
   *   The $message array structure containing all details of the message. If
   *   already sent ($send = TRUE), then the 'result' element will contain the
   *   success indicator of the email, failure being already written to the
   *   watchdog.
   */
  public function getMessage() {
    return $this->message;
  }

}
