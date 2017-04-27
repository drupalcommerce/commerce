<?php

namespace Drupal\commerce_tax;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Represents a tax rate.
 */
class TaxRate {

  /**
   * The ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The label.
   *
   * @var string
   */
  protected $label;

  /**
   * The amounts.
   *
   * @var \Drupal\commerce_tax\TaxRateAmount[]
   */
  protected $amounts;

  /**
   * Whether the tax rate is the default for its tax type.
   *
   * @var bool
   */
  protected $default;

  /**
   * Constructs a new TaxRate instance.
   *
   * @param array $definition
   *   The definition.
   */
  public function __construct(array $definition) {
    foreach (['id', 'label', 'amounts'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new \InvalidArgumentException(sprintf('Missing required property "%s".', $required_property));
      }
    }
    if (!is_array($definition['amounts'])) {
      throw new \InvalidArgumentException(sprintf('The property "amounts" must be an array.'));
    }

    $this->id = $definition['id'];
    $this->label = $definition['label'];
    foreach ($definition['amounts'] as $amount_definition) {
      $this->amounts[] = new TaxRateAmount($amount_definition);
    }
    $this->default = !empty($definition['default']);
  }

  /**
   * Gets the ID.
   *
   * @return string
   *   The ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Gets the label.
   *
   * For example: "Standard".
   *
   * @return string
   *   The label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Gets the amounts.
   *
   * @return \Drupal\commerce_tax\TaxRateAmount[]
   *   The amounts.
   */
  public function getAmounts() {
    return $this->amounts;
  }

  /**
   * Gets the amount valid for the given date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The date.
   *
   * @return \Drupal\commerce_tax\TaxRateAmount|null
   *   The amount, or NULL if none found.
   */
  public function getAmount(DrupalDateTime $date = NULL) {
    // Default to the current date.
    $date = $date ?: new DrupalDateTime();
    // Amount start/end dates don't include the time, so discard the time
    // portion of the given date to make the matching precise.
    $date->setTime(0, 0);
    foreach ($this->amounts as $amount) {
      $start_date = $amount->getStartDate();
      $end_date = $amount->getEndDate();
      // Match the date against the amount start/end dates.
      if (($start_date <= $date) && (!$end_date || $end_date > $date)) {
        return $amount;
      }
    }
    return NULL;
  }

  /**
   * Gets whether the tax rate is the default for its tax type.
   *
   * When resolving the tax rate for a specific tax type, the default tax
   * rate is returned if no other resolver provides a more applicable one.
   *
   * @return bool
   *   TRUE if this is the default tax rate, FALSE otherwise.
   */
  public function isDefault() {
    return !empty($this->default);
  }

}
