<?php

namespace Drupal\commerce_promotion\Entity;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_store\Entity\EntityStoresInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\PromotionOfferInterface;

/**
 * Defines the interface for promotions.
 */
interface PromotionInterface extends ContentEntityInterface, EntityStoresInterface {

  const COMPATIBLE_ANY = 'any';
  const COMPATIBLE_NONE = 'none';

  /**
   * Gets the promotion name.
   *
   * This name is admin-facing.
   *
   * @return string
   *   The promotion name.
   */
  public function getName();

  /**
   * Sets the promotion name.
   *
   * @param string $name
   *   The promotion name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the promotion display name.
   *
   * This name is user-facing.
   * Shown in the order total summary.
   *
   * @return string
   *   The promotion display name. If empty, use t('Discount').
   */
  public function getDisplayName();

  /**
   * Sets the promotion display name.
   *
   * @param string $display_name
   *   The promotion display name.
   *
   * @return $this
   */
  public function setDisplayName($display_name);

  /**
   * Gets the promotion description.
   *
   * @return string
   *   The promotion description.
   */
  public function getDescription();

  /**
   * Sets the promotion description.
   *
   * @param string $description
   *   The promotion description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Gets the promotion order types.
   *
   * @return \Drupal\commerce_order\Entity\OrderTypeInterface[]
   *   The promotion order types.
   */
  public function getOrderTypes();

  /**
   * Sets the promotion order types.
   *
   * @param \Drupal\commerce_order\Entity\OrderTypeInterface[] $order_types
   *   The promotion order types.
   *
   * @return $this
   */
  public function setOrderTypes(array $order_types);

  /**
   * Gets the promotion order type IDs.
   *
   * @return int[]
   *   The promotion order type IDs.
   */
  public function getOrderTypeIds();

  /**
   * Sets the promotion order type IDs.
   *
   * @param int[] $order_type_ids
   *   The promotion order type IDs.
   *
   * @return $this
   */
  public function setOrderTypeIds(array $order_type_ids);

  /**
   * Gets the offer.
   *
   * @return \Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\PromotionOfferInterface|null
   *   The offer, or NULL if not yet available.
   */
  public function getOffer();

  /**
   * Sets the offer.
   *
   * @param \Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\PromotionOfferInterface $offer
   *   The offer.
   *
   * @return $this
   */
  public function setOffer(PromotionOfferInterface $offer);

  /**
   * Gets the conditions.
   *
   * @return \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface[]
   *   The conditions.
   */
  public function getConditions();

  /**
   * Sets the conditions.
   *
   * @param \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface[] $conditions
   *   The conditions.
   *
   * @return $this
   */
  public function setConditions(array $conditions);

  /**
   * Gets the condition operator.
   *
   * @return string
   *   The condition operator. Possible values: AND, OR.
   */
  public function getConditionOperator();

  /**
   * Sets the condition operator.
   *
   * @param string $condition_operator
   *   The condition operator.
   *
   * @return $this
   */
  public function setConditionOperator($condition_operator);

  /**
   * Gets the coupon IDs.
   *
   * @return int[]
   *   The coupon IDs.
   */
  public function getCouponIds();

  /**
   * Gets the coupons.
   *
   * @return \Drupal\commerce_promotion\Entity\CouponInterface[]
   *   The coupons.
   */
  public function getCoupons();

  /**
   * Sets the coupons.
   *
   * @param \Drupal\commerce_promotion\Entity\CouponInterface[] $coupons
   *   The coupons.
   *
   * @return $this
   */
  public function setCoupons(array $coupons);

  /**
   * Gets whether the promotion has coupons.
   *
   * @return bool
   *   TRUE if the promotion has coupons, FALSE otherwise.
   */
  public function hasCoupons();

  /**
   * Adds a coupon.
   *
   * @param \Drupal\commerce_promotion\Entity\CouponInterface $coupon
   *   The coupon.
   *
   * @return $this
   */
  public function addCoupon(CouponInterface $coupon);

  /**
   * Removes a coupon.
   *
   * @param \Drupal\commerce_promotion\Entity\CouponInterface $coupon
   *   The coupon.
   *
   * @return $this
   */
  public function removeCoupon(CouponInterface $coupon);

  /**
   * Checks whether the promotion has a given coupon.
   *
   * @param \Drupal\commerce_promotion\Entity\CouponInterface $coupon
   *   The coupon.
   *
   * @return bool
   *   TRUE if the coupon was found, FALSE otherwise.
   */
  public function hasCoupon(CouponInterface $coupon);

  /**
   * Gets the promotion usage limit.
   *
   * Represents the maximum number of times the promotion can be used.
   * 0 for unlimited.
   *
   * @return int
   *   The promotion usage limit.
   */
  public function getUsageLimit();

  /**
   * Sets the promotion usage limit.
   *
   * @param int $usage_limit
   *   The promotion usage limit.
   *
   * @return $this
   */
  public function setUsageLimit($usage_limit);

  /**
   * Gets the promotion start date/time.
   *
   * The start date/time should always be used in the store timezone.
   * Since the promotion can belong to multiple stores, the timezone
   * isn't known at load/save time, and is provided by the caller instead.
   *
   * Note that the returned date/time value is the same in any timezone,
   * the "2019-10-17 10:00" stored value is returned as "2019-10-17 10:00 CET"
   * for "Europe/Berlin" and "2019-10-17 10:00 ET" for "America/New_York".
   *
   * @param string $store_timezone
   *   The store timezone. E.g. "Europe/Berlin".
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The promotion start date/time.
   */
  public function getStartDate($store_timezone = 'UTC');

  /**
   * Sets the promotion start date/time.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The promotion start date/time.
   *
   * @return $this
   */
  public function setStartDate(DrupalDateTime $start_date);

  /**
   * Gets the promotion end date/time.
   *
   * The end date/time should always be used in the store timezone.
   * Since the promotion can belong to multiple stores, the timezone
   * isn't known at load/save time, and is provided by the caller instead.
   *
   * Note that the returned date/time value is the same in any timezone,
   * the "2019-10-17 11:00" stored value is returned as "2019-10-17 11:00 CET"
   * for "Europe/Berlin" and "2019-10-17 11:00 ET" for "America/New_York".
   *
   * @param string $store_timezone
   *   The store timezone. E.g. "Europe/Berlin".
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The promotion end date/time.
   */
  public function getEndDate($store_timezone = 'UTC');

  /**
   * Sets the promotion end date/time.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $end_date
   *   The promotion end date/time.
   *
   * @return $this
   */
  public function setEndDate(DrupalDateTime $end_date = NULL);

  /**
   * Gets the promotion compatibility.
   *
   * @return string
   *   The compatibility.
   */
  public function getCompatibility();

  /**
   * Sets the promotion compatibility.
   *
   * @param string $compatibility
   *   The compatibility.
   *
   * @return $this
   */
  public function setCompatibility($compatibility);

  /**
   * Get whether the promotion is enabled.
   *
   * @return bool
   *   TRUE if the promotion is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets whether the promotion is enabled.
   *
   * @param bool $enabled
   *   Whether the promotion is enabled.
   *
   * @return $this
   */
  public function setEnabled($enabled);

  /**
   * Gets the weight.
   *
   * @return int
   *   The weight.
   */
  public function getWeight();

  /**
   * Sets the weight.
   *
   * @param int $weight
   *   The weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Checks whether the promotion is available for the given order.
   *
   * Ensures that the order type and store match the promotion's,
   * that the promotion is enabled, the current date matches the
   * start and end dates, and the usage limits are respected.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return bool
   *   TRUE if promotion is available, FALSE otherwise.
   */
  public function available(OrderInterface $order);

  /**
   * Checks whether the promotion can be applied to the given order.
   *
   * Ensures that the promotion is compatible with other
   * promotions on the order, and that the conditions pass.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return bool
   *   TRUE if promotion can be applied, FALSE otherwise.
   */
  public function applies(OrderInterface $order);

  /**
   * Applies the promotion to the given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function apply(OrderInterface $order);

}
