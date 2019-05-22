<?php

namespace Drupal\commerce_payment\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Defines the interface for payment methods.
 */
interface PaymentMethodInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, EntityWithPaymentGatewayInterface {

  /**
   * Gets the payment method type.
   *
   * @return \Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeInterface
   *   The payment method type.
   */
  public function getType();

  /**
   * Gets the payment gateway mode.
   *
   * A payment gateway in "live" mode can't manipulate payment methods created
   * while it was in "test" mode, and vice-versa.
   *
   * @return string
   *   The payment gateway mode.
   */
  public function getPaymentGatewayMode();

  /**
   * Gets the payment method remote ID.
   *
   * @return string
   *   The payment method remote ID.
   */
  public function getRemoteId();

  /**
   * Sets the payment method remote ID.
   *
   * @param string $remote_id
   *   The payment method remote ID.
   *
   * @return $this
   */
  public function setRemoteId($remote_id);

  /**
   * Gets the billing profile.
   *
   * Present only if the payment gateway collects billing information.
   *
   * @see \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface::collectsBillingInformation()
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   The billing profile entity, or NULL if none found.
   */
  public function getBillingProfile();

  /**
   * Sets the billing profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The billing profile entity.
   *
   * @return $this
   */
  public function setBillingProfile(ProfileInterface $profile);

  /**
   * Gets whether the payment method is reusable.
   *
   * @return bool
   *   TRUE if the payment method is reusable, FALSE otherwise.
   */
  public function isReusable();

  /**
   * Sets whether the payment method is reusable.
   *
   * @param bool $reusable
   *   Whether the payment method is reusable.
   *
   * @return $this
   */
  public function setReusable($reusable);

  /**
   * Gets whether this is the user's default payment method.
   *
   * @return bool
   *   TRUE if this is the user's default payment method, FALSE otherwise.
   */
  public function isDefault();

  /**
   * Sets whether this is the user's default payment method.
   *
   * @param bool $default
   *   Whether this is the user's default payment method.
   *
   * @return $this
   */
  public function setDefault($default);

  /**
   * Gets whether the payment method has expired.
   *
   * @return bool
   *   TRUE if the payment method has expired, FALSE otherwise.
   */
  public function isExpired();

  /**
   * Gets the payment method expiration timestamp.
   *
   * @return int
   *   The payment method expiration timestamp.
   */
  public function getExpiresTime();

  /**
   * Sets the payment method expiration timestamp.
   *
   * @param int $timestamp
   *   The payment method expiration timestamp.
   *
   * @return $this
   */
  public function setExpiresTime($timestamp);

  /**
   * Gets the payment method creation timestamp.
   *
   * @return int
   *   Creation timestamp of the payment.
   */
  public function getCreatedTime();

  /**
   * Sets the payment method creation timestamp.
   *
   * @param int $timestamp
   *   The payment method creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

}
