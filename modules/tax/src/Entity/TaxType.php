<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Entity\TaxType.
 */

namespace Drupal\commerce_tax\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use CommerceGuys\Zone\Model\ZoneInterface;
use CommerceGuys\Tax\Model\TaxTypeInterface;
use CommerceGuys\Tax\Model\TaxRateInterface;

/**
 * Defines the Tax Type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_tax_type",
 *   label = @Translation("Tax type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_tax\Form\TaxTypeForm",
 *       "edit" = "Drupal\commerce_tax\Form\TaxTypeForm",
 *       "delete" = "Drupal\commerce_tax\Form\TaxTypeDeleteForm"
 *     },
 *     "list_builder" = "Drupal\commerce_tax\TaxTypeListBuilder"
 *   },
 *   admin_permission = "administer stores",
 *   config_prefix = "commerce_tax_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "compound" = "compound",
 *     "tag" = "tag",
 *     "roundingMode" = "roundingMode",
 *     "rates" = "rates"
 *   },
 *   links = {
 *     "edit-form" = "/admin/commerce/config/tax/type/{commerce_tax_type}/edit",
 *     "delete-form" = "/admin/commerce/config/tax/type/{commerce_tax_type}/delete",
 *     "collection" = "/admin/commerce/config/tax/type"
 *   }
 * )
 */
class TaxType extends ConfigEntityBase implements TaxTypeInterface {

  /**
   * The tax type id.
   *
   * @var string
   */
  protected $id;

  /**
   * The tax type name.
   *
   * @var string
   */
  protected $name;

  /**
   * Whether the tax type is compound or not.
   *
   * @var bool
   */
  protected $compound;

  /**
   * The tax type rounding mode.
   */
  protected $roundingMode;

  /**
   * The tax type tag.
   *
   * @var string
   */
  protected $tag;

  /**
   * The tax type rates.
   *
   * @var array
   */
  protected $rates = [];

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
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->name = $name;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isCompound() {
    return $this->compound;
  }

  /**
   * {@inheritdoc}
   */
  public function setCompound($compound) {
    $this->compound = $compound;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoundingMode() {
    return $this->roundingMode;
  }

  /**
   * {@inheritdoc}
   */
  public function setRoundingMode($roundingMode) {
    $this->roundingMode = $roundingMode;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getZone() {
    // @todo
    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function setZone(ZoneInterface $zone) {
    // @todo
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTag() {
    return $this->tag;
  }

  /**
   * {@inheritdoc}
   */
  public function setTag($tag) {
    $this->tag = $tag;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRates() {
    return $this->rates;
  }

  /**
   * {@inheritdoc}
   */
  public function setRates($rates) {
    $this->rates = $rates;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasRates() {
    return count($this->rates) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function addRate(TaxRateInterface $rate) {
    $this->rates[] = $rate->getId();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeRate(TaxRateInterface $rate) {
    unset($this->rates[array_search($rate->getId(), $this->rates)]);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasRate(TaxRateInterface $rate) {
    return array_search($rate, $this->rates) !== FALSE;
  }

}
