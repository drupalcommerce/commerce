<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Entity\TaxRateAmount.
 */

namespace Drupal\commerce_tax\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the tax rate amount entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_tax_rate_amount",
 *   label = @Translation("Tax rate amount"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_tax\Form\TaxRateAmountForm",
 *       "edit" = "Drupal\commerce_tax\Form\TaxRateAmountForm",
 *       "delete" = "Drupal\commerce_tax\Form\TaxRateAmountDeleteForm",
 *     },
 *     "list_builder" = "Drupal\commerce_tax\TaxRateAmountListBuilder"
 *   },
 *   admin_permission = "administer stores",
 *   config_prefix = "commerce_tax_rate_amount",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "amount",
 *   },
 *   config_export = {
 *     "id",
 *     "amount",
 *     "startDate",
 *     "endDate",
 *     "rate",
 *   },
 *   links = {
 *     "edit-form" = "/admin/commerce/config/tax/amount/{commerce_tax_rate_amount}/edit",
 *     "delete-form" = "/admin/commerce/config/tax/amount/{commerce_tax_rate_amount}/delete",
 *     "collection" = "/admin/commerce/config/tax/amount"
 *   }
 * )
 */
class TaxRateAmount extends ConfigEntityBase implements TaxRateAmountInterface {

  /**
   * The tax rate.
   *
   * @var \Drupal\commerce_tax\Entity\TaxRateInterface
   */
  protected $rate;

  /**
   * The tax rate amount id.
   *
   * @var string
   */
  protected $id;

  /**
   * The tax rate amount amount.
   *
   * @var float
   */
  protected $amount;

  /**
   * The tax rate amount start date.
   *
   * @var \DateTimeInterface
   */
  protected $startDate;

  /**
   * The tax rate amount end date.
   *
   * @var \DateTimeInterface
   */
  protected $endDate;

  /**
   * {@inheritdoc}
   */
  public function getRate() {
    return $this->rate;
  }

  /**
   * {@inheritdoc}
   */
  public function setRate(TaxRateInterface $rate) {
    $this->rate = $rate->getId();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount() {
    return $this->amount;
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount($amount) {
    $this->amount = $amount;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartDate() {
    return $this->startDate;
  }

  /**
   * {@inheritdoc}
   */
  public function setStartDate(\DateTime $startDate) {
    $this->startDate = $startDate;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndDate() {
    return $this->endDate;
  }

  /**
   * {@inheritdoc}
   */
  public function setEndDate(\DateTime $endDate) {
    $this->endDate = $endDate;
    return $this;
  }

}
