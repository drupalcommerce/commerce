<?php

namespace Drupal\commerce_promotion\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of 'commerce_usage_limit_per_client'.
 *
 * @FieldWidget(
 *   id = "commerce_usage_limit_per_client",
 *   label = @Translation("Usage limit per client"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class UsageLimitPerClientWidget extends WidgetBase {

  /**
   * The name of the parent field using this widget.
   *
   * @var string
   */
  protected $parent;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : NULL;
    // We need to know the name of the field using this widget,
    // in order to then adapt his behavior.
    if (!empty($this->fieldDefinition->getItemDefinition()->getSetting('parent'))) {
      $this->parent = $this->fieldDefinition->getItemDefinition()->getSetting('parent');
    }
    else {
      $this->parent = $this->fieldDefinition->getName();
    }

    // A radio button of the parent element informs about the active state,
    // or not of the usage limitation. The visible status here depends on it.
    $radio_parents = array_merge($form['#parents'], [$this->parent, 0, 'limit']);
    $radio_path = array_shift($radio_parents);
    $radio_path .= '[' . implode('][', $radio_parents) . ']';

    $element['usage_limit_per_client'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of uses per client'),
      '#default_value' => $value,
      '#description' => $this->t('Limit the number of uses per client.'),
      '#states' => [
        'invisible' => [
          ':input[name="' . $radio_path . '"]' => ['value' => 0],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $new_values = [];
    foreach ($values as $key => $value) {
      if (!empty($form_state->getValue('usage_limit')[$key]['limit'])) {
        $new_values[$key] = $value['usage_limit_per_client'];
      }
    }
    return $new_values;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return in_array($entity_type, ['commerce_promotion', 'commerce_promotion_coupon']) && $field_name == 'usage_limit_per_client';
  }

}
