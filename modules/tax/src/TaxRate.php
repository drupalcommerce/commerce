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
   * The percentages.
   *
   * @var \Drupal\commerce_tax\TaxRatePercentage[]
   */
  protected $percentages;

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
    foreach (['id', 'label', 'percentages'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new \InvalidArgumentException(sprintf('Missing required property "%s".', $required_property));
      }
    }
    if (!is_array($definition['percentages'])) {
      throw new \InvalidArgumentException(sprintf('The property "percentages" must be an array.'));
    }

    $this->id = $definition['id'];
    $this->label = $definition['label'];
    foreach ($definition['percentages'] as $percentage_definition) {
      $this->percentages[] = new TaxRatePercentage($percentage_definition);
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
   * Gets the percentages.
   *
   * @return \Drupal\commerce_tax\TaxRatePercentage[]
   *   The percentages.
   */
  public function getPercentages() {
    return $this->percentages;
  }

  /**
   * Gets the array representation of the tax rate.
   *
   * @return array
   *   The array representation of the tax rate.
   */
  public function toArray() : array {
    return [
      'id' => $this->id,
      'default' => $this->default,
      'label' => $this->label,
      'percentages' => array_map(function (TaxRatePercentage $percentage) {
        return $percentage->toArray();
      }, $this->percentages),
    ];
  }

  /**
   * Gets the percentage valid for the given date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The date.
   *
   * @return \Drupal\commerce_tax\TaxRatePercentage|null
   *   The percentage, or NULL if none found.
   */
  public function getPercentage(DrupalDateTime $date = NULL) {
    // Default to the current date.
    $date = $date ?: new DrupalDateTime();
    // Unlike DateTime, DrupalDateTime objects can't be compared directly.
    // Convert them to timestamps, after discarding the time portion.
    $time = $date->setTime(0, 0, 0)->format('U');
    $timezone = $date->getTimezone();
    foreach ($this->percentages as $percentage) {
      $start_date = $percentage->getStartDate($timezone);
      $start_time = $start_date->setTime(0, 0, 0)->format('U');
      $end_date = $percentage->getEndDate($timezone);
      $end_time = $end_date ? $end_date->setTime(0, 0, 0)->format('U') : 0;

      if (($start_time <= $time) && (!$end_time || $end_time >= $time)) {
        return $percentage;
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
