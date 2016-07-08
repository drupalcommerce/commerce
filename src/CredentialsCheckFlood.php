<?php

namespace Drupal\commerce;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Flood\FloodInterface;

/**
 * Provides flood protection for login credential checks.
 *
 * @todo Replace with core version once #2431357 lands.
 */
class CredentialsCheckFlood implements CredentialsCheckFloodInterface {

  /**
   * The flood controller.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The flood configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The static cache of loaded accounts.
   *
   * @var \Drupal\Core\Session\AccountInterface[]
   */
  protected $accounts = [];

  /**
   * Constructs a new CredentialsCheckFlood object.
   *
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood controller.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(FloodInterface $flood, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->flood = $flood;
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config_factory->get('user.flood');
  }

  /**
   * {@inheritdoc}
   */
  public function register($ip, $name) {
    // Register a per-ip failed credentials check event.
    $this->flood->register('user.failed_login_ip', $this->config->get('ip_window'), $ip);

    // Register a per-user failed credentials check event.
    if ($identifier = $this->getAccountIdentifier($ip, $name)) {
      $this->flood->register('user.failed_login_user', $this->config->get('user_window'), $identifier);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearHost($ip) {
    $this->flood->clear('user.failed_login_ip', $ip);
  }

  /**
   * {@inheritdoc}
   */
  public function clearAccount($ip, $name) {
    if ($identifier = $this->getAccountIdentifier($ip, $name)) {
      $this->flood->clear('user.failed_login_user', $identifier);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowedHost($ip) {
    return $this->flood->isAllowed('user.failed_login_ip', $this->config->get('ip_limit'), $this->config->get('ip_window'), $ip);
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowedAccount($ip, $name) {
    $allowed = TRUE;
    if ($identifier = $this->getAccountIdentifier($ip, $name)) {
      $allowed = $this->flood->isAllowed('user.failed_login_user', $this->config->get('user_limit'), $this->config->get('user_window'), $identifier);
    }

    return $allowed;
  }

  /**
   * Gets the identifier used to register account flood events.
   *
   * @param string $ip
   *   The client IP address.
   * @param string $name
   *   The account name.
   *
   * @return string|null
   *   The flood identifier name or NULL if there is no account with the
   *   given name.
   */
  protected function getAccountIdentifier($ip, $name) {
    if (!isset($this->accounts[$name])) {
      $storage = $this->entityTypeManager->getStorage('user');
      $account_by_name = $storage->loadByProperties(['name' => $name]);
      $this->accounts[$name] = reset($account_by_name);
    }
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $this->accounts[$name];
    if ($account) {
      if ($this->config->get('uid_only')) {
        // Register flood events based on the uid only, so they apply for any
        // IP address. This is the most secure option.
        return $account->id();
      }
      else {
        // The default identifier is a combination of uid and IP address. This
        // is less secure but more resistant to denial-of-service attacks that
        // could lock out all users with public user names.
        return $account->id() . '-' . $ip;
      }
    }
  }

}
