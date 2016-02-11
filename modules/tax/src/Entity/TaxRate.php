<?php

namespace Drupal\commerce_tax\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the tax rate entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_tax_rate",
 *   label = @Translation("Tax rate"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_tax\Form\TaxRateForm",
 *       "edit" = "Drupal\commerce_tax\Form\TaxRateForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\commerce_tax\TaxRateListBuilder"
 *   },
 *   admin_permission = "administer stores",
 *   config_prefix = "commerce_tax_rate",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *   },
 *   config_export = {
 *     "id",
 *     "type",
 *     "name",
 *     "default",
 *     "amounts",
 *   },
 *   links = {
 *     "edit-form" = "/admin/commerce/config/tax-rates/{commerce_tax_rate}/edit",
 *     "delete-form" = "/admin/commerce/config/tax-rates/{commerce_tax_rate}/delete",
 *     "collection" = "/admin/commerce/config/tax-rates"
 *   }
 * )
 */
class TaxRate extends ConfigEntityBase implements TaxRateInterface {

  /**
   * The tax rate ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The tax type ID.
   *
   * @var string
   */
  protected $type;

  /**
   * The loaded tax type.
   *
   * @var \Drupal\commerce_tax\Entity\TaxTypeInterface
   */
  protected $loadedType;

  /**
   * The tax rate name.
   *
   * @var string
   */
  protected $name;

  /**
   * Whether the tax rate is the default for its tax type.
   *
   * @var boolean
   */
  protected $default;

  /**
   * The tax rate amount IDs.
   *
   * @var string[]
   */
  protected $amounts = [];

  /**
   * The loaded tax rate amounts.
   *
   * @var \Drupal\commerce_tax\Entity\TaxRateAmountInterface[]
   */
  protected $loadedAmounts = [];

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeId() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    if (!$this->loadedType) {
      $this->loadedType = $this->entityTypeManager()->getStorage('commerce_tax_type')->load($this->type);
    }
    return $this->loadedType;
  }

  /**
   * {@inheritdoc}
   */
  public function setType(TaxTypeInterface $type) {
    $this->type = $type->id();
    $this->loadedType = $type;
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
  public function isDefault() {
    return $this->default;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefault($default) {
    $this->default = $default;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmounts() {
    if (!$this->loadedAmounts) {
      $storage = $this->entityTypeManager()->getStorage('commerce_tax_rate_amount');
      $this->loadedAmounts = $storage->loadMultiple($this->amounts);
    }
    return $this->loadedAmounts;
  }

  /**
   * {@inheritdoc}
   */
  public function setAmounts(array $amounts) {
    $this->amounts = array_map(function ($amount) {
      return $amount->id();
    }, $amounts);
    $this->loadedAmounts = $amounts;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount(\DateTime $date) {
    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAmounts() {
    return count($this->amounts) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function addAmount(TaxRateAmountInterface $amount) {
    if (!$this->hasAmount($amount)) {
      $this->amounts[] = $amount->getId();
      $this->loadedAmounts = [];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeAmount(TaxRateAmountInterface $amount) {
    $index = array_search($amount->getId(), $this->amounts);
    if ($index !== FALSE) {
      unset($this->amounts[$index]);
      $this->loadedAmounts = [];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAmount(TaxRateAmountInterface $amount) {
    return array_search($amount->getId(), $this->amounts) !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $parameters = [];
    if ($rel == 'collection') {
      $parameters['commerce_tax_type'] = $this->type;
    }
    else {
      $parameters['commerce_tax_rate'] = $this->id;
    }

    return $parameters;
   }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    // Ensure there's only one default rate.
    if ($this->isDefault()) {
      /** @var \Drupal\commerce_tax\Entity\TaxRateInterface[] $other_rates */
      $other_rates = $this->getType()->getRates();
      foreach ($other_rates as $other_rate) {
        if ($other_rate->isDefault() && $other_rate->id() != $this->id()) {
          $other_rate->setDefault(FALSE);
          $other_rate->save();
        }
      }
    }

    // Add a reference to the parent tax type.
    if (!$update) {
      $tax_type = $this->getType();
      $tax_type->addRate($this);
      $tax_type->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    /** @var \Drupal\commerce_tax\Entity\TaxRateInterface $entity */
    parent::postDelete($storage, $entities);

    // Delete the tax rates amounts of each tax rate.
    $amounts = [];
    foreach ($entities as $entity) {
      foreach ($entity->getAmounts() as $amount) {
        $amounts[$amount->id()] = $amount;
      }
    }
    /** @var \Drupal\Core\Entity\EntityStorageInterface $rate_storage */
    $amount_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_tax_rate_amount');
    $amount_storage->delete($amounts);
  }

}
