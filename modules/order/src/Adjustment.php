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
   * The source identifier of the adjustment.
   *
   * Points to the source object, if known. For example, a promotion entity for
   * a discount adjustment.
   *
   * @var string
   */
  protected $sourceId;

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

    $this->type = $definition['type'];
    $this->label = $definition['label'];
    $this->amount = $definition['amount'];
    if (!empty($definition['source_id'])) {
      $this->sourceId = $definition['source_id'];
    }
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
   * Get the source identifier.
   *
   * @return string
   *   The source identifier.
   */
  public function getSourceId() {
    return $this->sourceId;
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

}
