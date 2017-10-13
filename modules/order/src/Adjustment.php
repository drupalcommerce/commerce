<?php

namespace Drupal\commerce_order;

use Drupal\commerce_price\Price;

/**
 * Represents an adjustment.
 */
final class Adjustment {

  /**
   * The adjustment type.
   *
   * @var string
   */
  protected $type;

  /**
   * The adjustment label.
   *
   * @var string
   */
  protected $label;

  /**
   * The adjustment amount.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $amount;

  /**
   * The adjustment percentage.
   *
   * @var string
   */
  protected $percentage;

  /**
   * The source identifier of the adjustment.
   *
   * Points to the source object, if known. For example, a promotion entity for
   * a discount adjustment.
   *
   * @var string
   */
  protected $sourceId;

  /**
   * Whether the adjustment is included in the base price.
   *
   * @var bool
   */
  protected $included = FALSE;

  /**
   * Whether the adjustment is locked.
   *
   * @var bool
   */
  protected $locked = FALSE;

  /**
   * Constructs a new Adjustment object.
   *
   * @param array $definition
   *   The definition.
   */
  public function __construct(array $definition) {
    foreach (['type', 'label', 'amount'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new \InvalidArgumentException(sprintf('Missing required property %s.', $required_property));
      }
    }
    if (!$definition['amount'] instanceof Price) {
      throw new \InvalidArgumentException(sprintf('Property "amount" should be an instance of %s.', Price::class));
    }
    $adjustment_type_manager = \Drupal::service('plugin.manager.commerce_adjustment_type');
    $types = $adjustment_type_manager->getDefinitions();
    if (empty($types[$definition['type']])) {
      throw new \InvalidArgumentException(sprintf('%s is an invalid adjustment type.', $definition['type']));
    }
    if (!empty($definition['percentage'])) {
      if (is_float($definition['percentage'])) {
        throw new \InvalidArgumentException(sprintf('The provided percentage "%s" must be a string, not a float.', $definition['percentage']));
      }
      if (!is_numeric($definition['percentage'])) {
        throw new \InvalidArgumentException(sprintf('The provided percentage "%s" is not a numeric value.', $definition['percentage']));
      }
    }
    // Assume that 'custom' adjustments are always locked, for BC reasons.
    if ($definition['type'] == 'custom' && !isset($definition['locked'])) {
      $definition['locked'] = TRUE;
    }

    $this->type = $definition['type'];
    $this->label = (string) $definition['label'];
    $this->amount = $definition['amount'];
    $this->percentage = !empty($definition['percentage']) ? $definition['percentage'] : NULL;
    $this->sourceId = !empty($definition['source_id']) ? $definition['source_id'] : NULL;
    $this->included = !empty($definition['included']);
    $this->locked = !empty($definition['locked']);
  }

  /**
   * Gets the adjustment type.
   *
   * @return string
   *   The adjustment type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Gets the adjustment label.
   *
   * @return string
   *   The adjustment label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Gets the adjustment amount.
   *
   * @return \Drupal\commerce_price\Price
   *   The adjustment amount.
   */
  public function getAmount() {
    return $this->amount;
  }

  /**
   * Gets whether the adjustment is positive.
   *
   * @return bool
   *   TRUE if the adjustment is positive, FALSE otherwise.
   */
  public function isPositive() {
    return $this->amount->getNumber() >= 0;
  }

  /**
   * Gets whether the adjustment is negative.
   *
   * @return bool
   *   TRUE if the adjustment is negative, FALSE otherwise.
   */
  public function isNegative() {
    return $this->amount->getNumber() < 0;
  }

  /**
   * Gets the adjustment percentage.
   *
   * @return string|null
   *   The percentage as a decimal. For example, "0.2" for a 20% adjustment.
   *   Otherwise NULL, if the adjustment was not calculated from a percentage.
   */
  public function getPercentage() {
    return $this->percentage;
  }

  /**
   * Get the source identifier.
   *
   * @return string
   *   The source identifier.
   */
  public function getSourceId() {
    return $this->sourceId;
  }

  /**
   * Gets whether the adjustment is included in the base price.
   *
   * @return bool
   *   TRUE if the adjustment is included in the base price, FALSE otherwise.
   */
  public function isIncluded() {
    return $this->included;
  }

  /**
   * Gets whether the adjustment is locked.
   *
   * Locked adjustments are not removed during the order refresh process.
   *
   * @return bool
   *   TRUE if the adjustment is locked, FALSE otherwise.
   */
  public function isLocked() {
    return $this->locked;
  }

}
