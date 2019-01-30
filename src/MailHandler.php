<?php

namespace Drupal\commerce;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationManager;
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
   * The language default.
   *
   * @var \Drupal\Core\Language\LanguageDefault
   */
  protected $languageDefault;

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
   * @param \Drupal\Core\Language\LanguageDefault $language_default
   *   The language default.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageDefault $language_default, LanguageManagerInterface $language_manager, MailManagerInterface $mail_manager) {
    $this->storeStorage = $entity_type_manager->getStorage('commerce_store');
    $this->languageDefault = $language_default;
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

    // Change the active language to the one preferred by the customer
    // to ensure the email is properly translated.
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    $preferred_langcode = $account->getPreferredLangcode();
    if ($default_langcode !== $preferred_langcode) {
      $this->changeActiveLanguage($preferred_langcode);
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

    $message = $this->mailManager->mail('commerce', $params['id'], $to, $preferred_langcode, $params);

    // Revert back to the original active language.
    if ($default_langcode !== $preferred_langcode) {
      $this->changeActiveLanguage($default_langcode);
    }

    return (bool) $message['result'];
  }

  /**
   * Changes the active language for translations.
   *
   * @param string $langcode
   *   The langcode.
   */
  protected function changeActiveLanguage($langcode) {
    if (!$this->languageManager->isMultilingual()) {
      return;
    }
    $language = $this->languageManager->getLanguage($langcode);
    if (!$language) {
      return;
    }
    // The language manager has no method for overriding the default
    // language, like it does for config overrides. We have to change the
    // default language service's current language.
    // @see https://www.drupal.org/project/drupal/issues/3029010
    $this->languageDefault->set($language);
    $this->languageManager->setConfigOverrideLanguage($language);
    $this->languageManager->reset();

    // The default string_translation service, TranslationManager, has a
    // setDefaultLangcode method. However, this method is not present on
    // either of its interfaces. Therefore we check for the concrete class
    // here so that any swapped service does not break the application.
    // @see https://www.drupal.org/project/drupal/issues/3029003
    $string_translation = $this->getStringTranslation();
    if ($string_translation instanceof TranslationManager) {
      $string_translation->setDefaultLangcode($language->getId());
      $string_translation->reset();
    }
  }

}
