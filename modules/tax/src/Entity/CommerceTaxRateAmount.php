<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Entity\CommerceTaxRateAmount.
 */

namespace Drupal\commerce_tax\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use CommerceGuys\Tax\Model\TaxRateInterface;
use CommerceGuys\Tax\Model\TaxRateAmountInterface;

/**
 * Defines the Tax Rate Amount configuration entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_tax_rate_amount",
 *   label = @Translation("Tax rate amount"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_tax\Form\CommerceTaxRateAmountForm",
 *       "edit" = "Drupal\commerce_tax\Form\CommerceTaxRateAmountForm",
 *       "delete" = "Drupal\commerce_tax\Form\CommerceTaxRateAmountDeleteForm",
 *     },
 *     "list_builder" = "Drupal\commerce_tax\Controller\CommerceTaxRateAmountListBuilder"
 *   },
 *   admin_permission = "administer stores",
 *   config_prefix = "commerce_tax_rate_amount",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "amount",
 *     "startDate" = "startDate",
 *     "endDate" = "endDate",
 *     "rate" = "rate"
 *   },
 *   links = {
 *     "edit-form" = "/admin/commerce/config/tax/amount/{commerce_tax_rate_amount}/edit",
 *     "delete-form" = "/admin/commerce/config/tax/amount/{commerce_tax_rate_amount}/delete"
 *   }
 * )
 */
class CommerceTaxRateAmount extends ConfigEntityBase implements TaxRateAmountInterface {

  /**
   * The tax rate.
   *
   * @var \CommerceGuys\Tax\Model\TaxRateInterface
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
  public function setRate(TaxRateInterface $rate = null) {
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
  public function setId($id) {
    $this->id = $id;

    return $this;
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
