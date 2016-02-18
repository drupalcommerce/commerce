<?php

namespace Drupal\commerce_product\Plugin\Field\FieldWidget;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Plugin implementation of the 'commerce_product_variation_attributes ' widget.
 *
 * @FieldWidget(
 *   id = "commerce_product_variation_attributes ",
 *   label = @Translation("Product variation attributes"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ProductVariationAttributesWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product\Entity\Product $product */
    $product = $form['#product'];
    $variations = $product->variations->referencedEntities();
    if (count($variations) === 0) {
      // Signal to the parent form that there are no variations to select.
      $element['variation'] = [
        '#type' => 'value',
        '#value' => 0,
      ];
      return $element;
    }
    elseif (count($variations) === 1) {
      // Preselect the only possible variation.
      // @todo Limit this behavior to products with no attributes instead.
      $selected_variation = reset($variations);
      $element['variation'] = [
        '#type' => 'value',
        '#value' => $selected_variation->id(),
      ];
      return $element;
    }

    // Build the full attribute form.
    $selected_variation = $this->selectVariationFromUserInput($variations, $form_state);
    $element['variation'] = [
      '#type' => 'value',
      '#value' => $selected_variation->id(),
    ];
    $element['attributes'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['attribute-widgets'],
      ],
    ];
    foreach ($this->getAttributeInfo($selected_variation, $variations) as $field_name => $attribute) {
      $element['attributes'][$field_name] = [
        '#type' => $attribute['type'],
        '#title' => $attribute['title'],
        '#options' => $attribute['values'],
        '#required' => $attribute['required'],
        '#default_value' => $selected_variation->get($field_name)->target_id,
        '#ajax' => [
          'callback' => '::ajaxRefresh',
          'wrapper' => $form['#wrapper_id'],
        ],
      ];
      // Convert the _none option into #empty_value.
      if (isset($element['attributes'][$field_name]['options']['_none'])) {
        if (!$element['attributes'][$field_name]['#required']) {
          $element['attributes'][$field_name]['#empty_value'] = '';
        }
        unset($element['attributes'][$field_name]['options']['_none']);
      }
      // 1 required value -> Disable the element to skip unneeded ajax calls.
      if ($attribute['required'] && count($attribute['values']) === 1) {
        $element['attributes'][$field_name]['#disabled'] = TRUE;
      }
    }

    return $element;
  }


  /**
   * Selects a product variation based on user input containing attribute values.
   *
   * If there's no user input (form viewed for the first time), the default
   * variation is returned.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   An array of product variations.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface
   *   The selected variation.
   */
  protected function selectVariationFromUserInput(array $variations, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    $current_variation = reset($variations);
    if (!empty($user_input)) {
      $attributes = $user_input['attributes'];
      foreach ($variations as $variation) {
        $match = TRUE;
        foreach ($attributes as $field_name => $value) {
          if ($variation->get($field_name)->target_id != $value) {
            $match = FALSE;
          }
        }

        if ($match) {
          $current_variation = $variation;
          break;
        }
      }
    }

    return $current_variation;
  }

  /**
   * Gets the attribute information for the selected product variation.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $selected_variation
   *   The selected product variation.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   The available product variations.
   *
   * @return array[]
   *   The attribute information, keyed by field name.
   */
  protected function getAttributeInfo(ProductVariationInterface $selected_variation, array $variations) {
    $attributes = [];
    /** @var \Drupal\Core\Field\FieldConfigInterface[] $field_definitions */
    $field_definitions = $selected_variation->getAttributeFieldDefinitions();
    $field_names = array_keys($field_definitions);
    $index = 0;
    foreach ($field_definitions as $field) {
      $field_name = $field->getName();
      $third_party_settings = $field->getThirdPartySettings('commerce_product');
      if (!empty($third_party_settings['attribute_widget_title'])) {
        $attribute_label = $third_party_settings['attribute_widget_title'];
      }
      else {
        $attribute_label = $field->label();
      }

      $attributes[$field_name] = [
        'field_name' => $field_name,
        'type' => $third_party_settings['attribute_widget'],
        'title' => $attribute_label,
        'required' => $field->isRequired(),
      ];
      // The first attribute gets all values. Every next attribute gets only
      // the values from variations matching the previous attribute value.
      // For 'Color' and 'Size' attributes that means getting the colors of all
      // variations, but only the sizes of variations with the selected color.
      $callback = NULL;
      if ($index > 0) {
        $previous_field_name = $field_names[$index - 1];
        $previous_field_value = $selected_variation->get($previous_field_name)->target_id;
        $callback = function ($variation) use ($previous_field_name, $previous_field_value) {
          return $variation->get($previous_field_name)->target_id == $previous_field_value;
        };
      }

      $attributes[$field_name]['values'] = $this->getAttributeValues($variations, $field_name, $callback);
      $index++;
    }
    // Filter out attributes with no values.
    $attributes = array_filter($attributes, function ($attribute) {
      return !empty($attribute['values']);
    });

    return $attributes;
  }
  /**
   * Gets the attribute values of a given set of variations.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   The variations.
   * @param string $field_name
   *   The field name of the attribute.
   * @param callable|NULL $callback
   *   An optional callback to use for filtering the list.
   *
   * @return array[]
   *   The attribute values, keyed by attribute id.
   */
  protected function getAttributeValues(array $variations, $field_name, callable $callback = NULL) {
    $values = [];
    foreach ($variations as $variation) {
      if (is_null($callback) || call_user_func($callback, $variation)) {
        if (!$variation->get($field_name)->isEmpty()) {
          /** @var \Drupal\Core\Entity\ContentEntityInterface $attribute_value */
          $attribute_value = $variation->get($field_name)->entity;
          $values[$attribute_value->id()] = $attribute_value->label();
        }
        else {
          $values['_none'] = '';
        }
      }
    }

    return $values;
  }

  /**
   * Ajax callback.
   */
  public function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form;
  }


}
