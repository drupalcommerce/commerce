<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\Entity\TaxType.
 */

namespace Drupal\commerce_tax\Entity;

use Drupal\address\ZoneInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use CommerceGuys\Tax\Enum\GenericLabel;

/**
 * Defines the tax type entity class.
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
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "genericLabel",
 *     "compound",
 *     "displayInclusive",
 *     "roundingMode",
 *     "tag",
 *     "rates",
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
   * The tax type generic label.
   *
   * @var string
   */
  protected $genericLabel;

  /**
   * Whether the tax type is compound.
   *
   * @var bool
   */
  protected $compound = FALSE;

  /**
   * Whether the tax type is display inclusive.
   *
   * @var bool
   */
  protected $displayInclusive = FALSE;

  /**
   * The tax type rounding mode.
   */
  protected $roundingMode;

  /**
   * The tax type zone.
   *
   * @var \Drupal\address\ZoneInterface
   */
  protected $zone;

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
  public function getGenericLabel() {
    return $this->genericLabel;
  }

  /**
   * {@inheritdoc}
   */
  public function setGenericLabel($genericLabel) {
    GenericLabel::assertExists($genericLabel);
    $this->genericLabel = $genericLabel;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isCompound() {
    return !empty($this->compound);
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
  public function isDisplayInclusive() {
    return !empty($this->displayInclusive);
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplayInclusive($displayInclusive) {
    $this->displayInclusive = $displayInclusive;
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
    return $this->zone;
  }

  /**
   * {@inheritdoc}
   */
  public function setZone(ZoneInterface $zone) {
    $this->zone = $zone;
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

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Delete all tax rates of each tax type.
    foreach ($entities as $entity) {
      if ($entity->hasRates()) {
        $rateStorage = \Drupal::service('entity_type.manager')->getStorage('commerce_tax_rate');
        $rates = $rateStorage->loadMultiple($entity->getRates());
        $rateStorage->delete($rates);
      }
    }
  }

}
