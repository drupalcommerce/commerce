<?php

namespace Drupal\commerce_product\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;
use Drupal\Component\Utility\Html as HtmlUtility;

/**
 * Provides a form input element for rendering attributes as radio buttons.
 *
 * The options must be an array of attribute values, keyed by the entity's ID.
 *
 * Example usage:
 * @code
 * $form['rendered_attributes'] = [
 *   '#type' => 'commerce_product_rendered_attribute',
 *   '#title' => $this->t('Attributes'),
 *   '#default_value' => 1,
 *   '#options' => [0 => 'Red', 1 => 'Blue'],
 * ];
 * @endcode
 *
 * @FormElement("commerce_product_rendered_attribute")
 */
class CommerceProductRenderedAttribute extends Radios {

  /**
   * Expands a radios element into individual radio elements.
   */
  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form) {
    if (count($element['#options']) > 0) {
      $storage = \Drupal::entityTypeManager()->getStorage('commerce_product_attribute_value');
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('commerce_product_attribute_value');
      /** @var \Drupal\Core\Render\RendererInterface $renderer */
      $renderer = \Drupal::service('renderer');
      $attribute_values = $storage->loadMultiple(array_keys($element['#options']));

      $weight = 0;
      foreach ($element['#options'] as $key => $choice) {
        $rendered_attribute = $view_builder->view($attribute_values[$key], 'add_to_cart');
        $attributes = $element['#attributes'];
        if (isset($element['#default_value']) && $element['#default_value'] == $key) {
          $attributes['class'][] = 'product--rendered-attribute__selected';
        }
        // Maintain order of options as defined in #options, in case the element
        // defines custom option sub-elements, but does not define all option
        // sub-elements.
        $weight += 0.001;

        $element += [$key => []];
        // Generate the parents as the autogenerator does, so we will have a
        // unique id for each radio button.
        $parents_for_id = array_merge($element['#parents'], [$key]);
        $element[$key] += [
          '#type' => 'radio',
          '#title' => $renderer->render($rendered_attribute),
          '#return_value' => $key,
          '#default_value' => isset($element['#default_value']) ? $element['#default_value'] : FALSE,
          '#attributes' => $attributes,
          '#parents' => $element['#parents'],
          '#id' => HtmlUtility::getUniqueId('edit-' . implode('-', $parents_for_id)),
          '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
          // Errors should only be shown on the parent radios element.
          '#error_no_message' => TRUE,
          '#weight' => $weight,
        ];
      }
    }

    $element['#attached']['library'][] = 'commerce_product/rendered-attributes';
    $element['#attributes']['class'][] = 'product--rendered-attribute';

    return $element;
  }

}
