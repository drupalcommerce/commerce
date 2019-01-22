<?php

namespace Drupal\commerce;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

class MailHandler implements MailHandlerInterface {

  use StringTranslationTrait;

  /**
   * The store storage.
   *
   * @var \Drupal\commerce_store\StoreStorageInterface
   */
  protected $storeStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a new MailHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The current store.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, MailManagerInterface $mail_manager) {
    $this->storeStorage = $entity_type_manager->getStorage('commerce_store');
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function sendEmail(UserInterface $account, $subject, array $body, array $params = []) {
    if ($account->isAnonymous() && empty($params['to'])) {
      throw new \InvalidArgumentException('The "to" parameter is required when emailing an anonymous user.');
    }
    $to = '';
    if (!empty($params['to'])) {
      $to = $params['to'];
    }
    elseif ($account->isAuthenticated()) {
      $to = $account->getEmail();
    }
    // The user has no email set, and no override was provided. Stop here.
    if (!$to) {
      return FALSE;
    }

    $default_store = $this->storeStorage->loadDefault();
    $default_params = [
      'headers' => [
        'Content-Type' => 'text/html; charset=UTF-8;',
        'Content-Transfer-Encoding' => '8Bit',
      ],
      'id' => 'mail',
      'from' => $default_store->getEmail(),
      'subject' => $subject,
      // The body will be rendered in commerce_mail(), because that's what
      // MailManager expects. The correct theme and render context aren't
      // setup until then.
      'body' => $body,
    ];
    if (isset($params['bcc'])) {
      $default_params['headers']['Bcc'] = $params['bcc'];
    }
    $params = array_replace($default_params, $params);

    // Replicated logic from EmailAction and contact's MailHandler.
    if ($account->isAuthenticated()) {
      $langcode = $account->getPreferredLangcode();
    }
    else {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
    }
    $message = $this->mailManager->mail('commerce', $params['id'], $to, $langcode, $params);

    return (bool) $message['result'];
  }

}
