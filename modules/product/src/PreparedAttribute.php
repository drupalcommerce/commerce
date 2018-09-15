<?php

namespace Drupal\commerce_product;

/**
 * Represents a prepared attribute.
 *
 * @see \Drupal\commerce_product\ProductVariationAttributeMapperInterface::prepareAttributes()
 */
final class PreparedAttribute {

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
   * The element type.
   *
   * @var string
   */
  protected $elementType;

  /**
   * Whether the attribute is required.
   *
   * @var bool
   */
  protected $required;

  /**
   * The attribute values.
   *
   * @var string[]
   */
  protected $values;

  /**
   * Constructs a new PreparedAttribute instance.
   *
   * @param array $definition
   *   The definition.
   */
  public function __construct(array $definition) {
    foreach (['id', 'label', 'element_type', 'values'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new \InvalidArgumentException(sprintf('Missing required property "%s".', $required_property));
      }
    }
    if (!is_array($definition['values'])) {
      throw new \InvalidArgumentException(sprintf('The property "values" must be an array.'));
    }

    $this->id = $definition['id'];
    $this->label = $definition['label'];
    $this->elementType = $definition['element_type'];
    $this->required = isset($definition['required']) ? $definition['required'] : TRUE;
    $this->values = $definition['values'];
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
   * @return string
   *   The label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Gets the element type.
   *
   * @return string
   *   The element type.
   */
  public function getElementType() {
    return $this->elementType;
  }

  /**
   * Gets whether the attribute is required.
   *
   * @return bool
   *   TRUE if the attribute is required, FALSE otherwise.
   */
  public function isRequired() {
    return $this->required;
  }

  /**
   * Gets the attribute values.
   *
   * @return string[]
   *   The attribute values.
   */
  public function getValues() {
    return $this->values;
  }

  /**
   * Gets the array representation of the prepared attribute.
   *
   * @return array
   *   The array representation of the prepared attribute.
   */
  public function toArray() {
    return [
      'id' => $this->id,
      'label' => $this->label,
      'element_type' => $this->elementType,
      'required' => $this->required,
      'values' => $this->values,
    ];
  }

}
