<?php

namespace Drupal\commerce;

use Drupal\Core\Entity\EntityInterface;

/**
 * Represents a condition group.
 *
 * Meant to be instantiated directly.
 */
final class ConditionGroup {

  /**
   * The conditions.
   *
   * @var \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface[]
   */
  protected $conditions;

  /**
   * The operator.
   *
   * Possible values: AND, OR.
   *
   * @var string
   */
  protected $operator;

  /**
   * Constructs a new ConditionGroup object.
   *
   * @param \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface[] $conditions
   *   The conditions.
   * @param string $operator
   *   The operator. Possible values: AND, OR.
   *
   * @throws \InvalidArgumentException
   *   Thrown when an invalid operator is given.
   */
  public function __construct(array $conditions, string $operator) {
    if (!in_array($operator, ['AND', 'OR'])) {
      throw new \InvalidArgumentException(sprintf('Invalid operator "%s" given, expecting "AND" or "OR".', $operator));
    }

    $this->conditions = $conditions;
    $this->operator = $operator;
  }

  /**
   * Gets the conditions.
   *
   * @return \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface[]
   *   The conditions.
   */
  public function getConditions() : array {
    return $this->conditions;
  }

  /**
   * Gets the operator.
   *
   * @return string
   *   The operator. Possible values: AND, OR.
   */
  public function getOperator() : string {
    return $this->operator;
  }

  /**
   * Evaluates the condition group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the condition group has passed, FALSE otherwise.
   */
  public function evaluate(EntityInterface $entity) : bool {
    if (empty($this->conditions)) {
      return TRUE;
    }

    $boolean = $this->operator == 'AND' ? FALSE : TRUE;
    foreach ($this->conditions as $condition) {
      if ($condition->evaluate($entity) == $boolean) {
        return $boolean;
      }
    }

    return !$boolean;
  }

}
