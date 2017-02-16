<?php

namespace Drupal\commerce;

/**
 * Provides a flood service tailored to login credential checks.
 */
interface CredentialsCheckFloodInterface {

  /**
   * Registers a new failed credentials check by the given user.
   *
   * @param string $ip
   *   The client IP address.
   * @param string $name
   *   The account name.
   */
  public function register($ip, $name);

  /**
   * Clears failed credential checks from the given host.
   *
   * @param string $ip
   *   The client IP address.
   */
  public function clearHost($ip);

  /**
   * Clears failed credential checks by the given user.
   *
   * @param string $ip
   *   The client IP address.
   * @param string $name
   *   The account name.
   */
  public function clearAccount($ip, $name);

  /**
   * Whether or not a client machine is allowed to perform a credentials check.
   *
   * Independent of the per-user limit to catch attempts from one IP to
   * log in to many different user accounts. We have a reasonably high limit
   * since there may be only one apparent IP for all users at an institution.
   *
   * @param string $ip
   *   The client IP address.
   *
   * @return bool
   *   TRUE if credentials check is allowed, FALSE otherwise.
   */
  public function isAllowedHost($ip);

  /**
   * Whether or not a credentials check with the given account is allowed.
   *
   * @param string $ip
   *   The client IP address.
   * @param string $name
   *   The account name.
   *
   * @return bool
   *   TRUE if credentials check is allowed, FALSE otherwise.
   */
  public function isAllowedAccount($ip, $name);

}
