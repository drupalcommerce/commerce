<?php

namespace Drupal\commerce;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\Renderer;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
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
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The theme initialization.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * Constructs a new MailHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The current store.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $theme_initialization
   *   The theme initialization.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, MailManagerInterface $mail_manager, Renderer $renderer, ThemeManagerInterface $theme_manager, ThemeInitializationInterface $theme_initialization) {
    $this->storeStorage = $entity_type_manager->getStorage('commerce_store');
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
    $this->renderer = $renderer;
    $this->themeManager = $theme_manager;
    $this->themeInitialization = $theme_initialization;
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
    ];
    if (isset($params['bcc'])) {
      $default_params['headers']['Bcc'] = $params['bcc'];
    }
    $params = array_replace($default_params, $params);

    // Switch the theme to the configured mail theme.
    $mail_theme = NULL;
    $current_active_theme = $this->themeManager->getActiveTheme();

    // The Mail System module swaps out core's MailManager, adding a
    // getMailTheme() method. However, this method is not on any interface.
    if (method_exists($this->mailManager, 'getMailTheme')) {
      $mail_theme = $this->mailManager->getMailTheme();
      if ($mail_theme && $mail_theme != $current_active_theme->getName()) {
        $initialized_mail_theme = $this->themeInitialization->initTheme($mail_theme);
        $this->themeManager->setActiveTheme($initialized_mail_theme);
      }
    }

    try {
      $params['body'] = $this->renderer->executeInRenderContext(new RenderContext(), function () use ($body) {
        return $this->renderer->render($body);
      });
    }
    finally {
      // Revert the active theme.
      if ($mail_theme != $current_active_theme->getName()) {
        $this->themeManager->setActiveTheme($current_active_theme);
      }
    }

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
