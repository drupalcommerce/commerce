<?php

namespace Drupal\commerce_payment\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Defines the interface for payment methods.
 */
interface PaymentMethodInterface extends EntityChangedInterface, ContentEntityInterface, EntityOwnerInterface {

  /**
   * Gets the payment method type.
   *
   * @return \Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeInterface
   *   The payment method type.
   */
  public function getType();

  /**
   * Gets the payment gateway.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentGatewayInterface|null
   *   The payment gateway entity, or null if unknown.
   */
  public function getPaymentGateway();

  /**
   * Gets the payment gateway ID.
   *
   * @return int|null
   *   The payment gateway ID, or null if unknown.
   */
  public function getPaymentGatewayId();

  /**
   * Gets the payment method remote ID
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
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The billing profile entity.
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
   * Gets the billing profile ID.
   *
   * @return int
   *   The billing profile ID.
   */
  public function getBillingProfileId();

  /**
   * Sets the billing profile ID.
   *
   * @param int $billingProfileId
   *   The billing profile ID.
   *
   * @return $this
   */
  public function setBillingProfileId($billingProfileId);

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
