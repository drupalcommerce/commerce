<?php

namespace Drupal\commerce_tax\Entity;

use Drupal\address\Entity\ZoneInterface;
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
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *       "create" = "Drupal\entity\Routing\CreateHtmlRouteProvider",
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
 *     "zone",
 *     "tag",
 *     "rates",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/tax-types/add",
 *     "edit-form" = "/admin/commerce/config/tax-types/{commerce_tax_type}/edit",
 *     "delete-form" = "/admin/commerce/config/tax-types/{commerce_tax_type}/delete",
 *     "collection" = "/admin/commerce/config/tax-types"
 *   }
 * )
 */
class TaxType extends ConfigEntityBase implements TaxTypeInterface {

  /**
   * The tax type ID.
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
   *
   * @var int
   */
  protected $roundingMode;

  /**
   * The zone ID.
   *
   * @var string
   */
  protected $zone;

  /**
   * The loaded zone.
   *
   * @var \Drupal\address\Entity\ZoneInterface
   */
  protected $loadedZone;

  /**
   * The tax type tag.
   *
   * @var string
   */
  protected $tag;

  /**
   * The tax rate IDs.
   *
   * @var string[]
   */
  protected $rates = [];

  /**
   * The loaded tax rates.
   *
   * @var \Drupal\commerce_tax\Entity\TaxRateInterface[]
   */
  protected $loadedRates;

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
  public function setGenericLabel($generic_label) {
    GenericLabel::assertExists($generic_label);
    $this->genericLabel = $generic_label;
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
  public function setDisplayInclusive($display_inclusive) {
    $this->displayInclusive = $display_inclusive;
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
  public function setRoundingMode($rounding_mode) {
    $this->roundingMode = $rounding_mode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getZoneId() {
    return $this->zone;
  }

  /**
   * {@inheritdoc}
   */
  public function getZone() {
    if (!$this->loadedZone) {
      $this->loadedZone = $this->entityTypeManager()->getStorage('zone')->load($this->zone);
    }
    return $this->loadedZone;
  }

  /**
   * {@inheritdoc}
   */
  public function setZone(ZoneInterface $zone) {
    $this->zone = $zone->id();
    $this->loadedZone = $zone;
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
    if (!$this->loadedRates) {
      $storage = $this->entityTypeManager()->getStorage('commerce_tax_rate');
      $this->loadedRates = $storage->loadMultiple($this->rates);
    }
    return $this->loadedRates;
  }

  /**
   * {@inheritdoc}
   */
  public function setRates(array $rates) {
    $this->rates = array_map(function ($rate) {
      return $rate->id();
    }, $rates);
    $this->loadedRates = $rates;

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
    if (!$this->hasRate($rate)) {
      $this->rates[] = $rate->getId();
      $this->loadedRates = [];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeRate(TaxRateInterface $rate) {
    $index = array_search($rate->getId(), $this->rates);
    if ($index !== FALSE) {
      unset($this->rates[$index]);
      $this->loadedRates = [];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasRate(TaxRateInterface $rate) {
    return array_search($rate->id(), $this->rates) !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    /** @var \Drupal\commerce_tax\Entity\TaxTypeInterface[] $entities */
    parent::postDelete($storage, $entities);

    // Delete the tax rates of each tax type.
    $rates = [];
    foreach ($entities as $entity) {
      foreach ($entity->getRates() as $rate) {
        $rates[$rate->id()] = $rate;
      }
    }
    /** @var \Drupal\Core\Entity\EntityStorageInterface $rate_storage */
    $rate_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_tax_rate');
    $rate_storage->delete($rates);
  }

}
