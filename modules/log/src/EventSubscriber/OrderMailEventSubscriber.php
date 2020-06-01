<?php

namespace Drupal\commerce_log\EventSubscriber;

use Drupal\commerce\Event\CommerceEvents;
use Drupal\commerce\Event\PostMailSendEvent;
use Drupal\commerce_log\LogTemplateManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to order emails sent via the Commerce mail handler.
 */
class OrderMailEventSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The log template manager.
   *
   * @var \Drupal\commerce_log\LogTemplateManagerInterface
   */
  protected $logTemplateManager;

  /**
   * Constructs a new MailEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_log\LogTemplateManagerInterface $log_template_manager
   *   The log template manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LogTemplateManagerInterface $log_template_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logTemplateManager = $log_template_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      CommerceEvents::POST_MAIL_SEND => ['onMailSend'],
    ];
  }

  /**
   * Reacts to an order email being sent.
   *
   * @param \Drupal\commerce\Event\PostMailSendEvent $event
   *   The "post mail send" event.
   */
  public function onMailSend(PostMailSendEvent $event) {
    $params = $event->getParams();
    if (!isset($params['order'])) {
      return;
    }
    /** @var \Drupal\commerce_log\LogStorageInterface $log_storage */
    $log_storage = $this->entityTypeManager->getStorage('commerce_log');
    $message = $event->getMessage();
    $result = (bool) $message['result'];
    $definitions = $this->logTemplateManager->getDefinitions();

    // Check if we have a log template matching "mail_<mail_key>",
    // (e.g "mail_order_receipt"), otherwise, fallback to the generic
    // "order_mail" log template.
    // In case the email could not be delivered, we use the "failure" log
    // template, either the specific one, or the generic one in case
    // it does not exist.
    $template_id = $result ? 'mail_' . $params['id'] : 'mail_' . $params['id'] . '_failure';
    if (!isset($definitions[$template_id])) {
      $template_id = $result ? 'order_mail' : 'order_mail_failure';
    }

    $log_params = [
      'id' => $params['id'],
      'to_email' => $event->getMessage()['to'],
    ];
    $log_storage->generate($params['order'], $template_id, $log_params)->save();
  }

}
