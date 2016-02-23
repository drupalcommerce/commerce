<?php

namespace Drupal\commerce_tax\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

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
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
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
 *     "rate",
 *     "amount",
 *     "startDate",
 *     "endDate",
 *   },
 *   links = {
 *     "edit-form" = "/admin/commerce/config/tax-rate-amounts/{commerce_tax_rate_amount}/edit",
 *     "delete-form" = "/admin/commerce/config/tax-rate-amounts/{commerce_tax_rate_amount}/delete",
 *     "collection" = "/admin/commerce/config/tax-rate-amounts"
 *   }
 * )
 */
class TaxRateAmount extends ConfigEntityBase implements TaxRateAmountInterface {

  /**
   * The tax rate amount ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The tax rate ID.
   *
   * @var string
   */
  protected $rate;

  /**
   * The loaded tax rate.
   *
   * @var \Drupal\commerce_tax\Entity\TaxRateInterface
   */
  protected $loadedRate;

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
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getRateId() {
    return $this->rate;
  }

  /**
   * {@inheritdoc}
   */
  public function getRate() {
    if (!$this->loadedRate) {
      $this->loadedRate = $this->entityTypeManager()->getStorage('commerce_tax_rate')->load($this->rate);
    }
    return $this->loadedRate;
  }

  /**
   * {@inheritdoc}
   */
  public function setRate(TaxRateInterface $rate) {
    $this->rate = $rate->id();
    $this->loadedRate = $rate;
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
  public function setStartDate(\DateTime $start_date) {
    $this->startDate = $start_date;
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
  public function setEndDate(\DateTime $end_date) {
    $this->endDate = $end_date;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $parameters = [];
    if ($rel == 'collection') {
      $parameters['commerce_tax_rate'] = $this->rate;
    }
    else {
      $parameters['commerce_tax_rate_amount'] = $this->id;
    }

    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    if (!$update) {
      // Add a reference to the parent tax rate.
      $tax_rate = $this->getRate();
      $tax_rate->addAmount($this);
      $tax_rate->save();
    }
  }

}
