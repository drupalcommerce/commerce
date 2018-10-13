<?php

namespace Drupal\commerce_product\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'commerce_product_single_variation' widget.
 *
 * @FieldWidget(
 *   id = "commerce_product_single_variation",
 *   label = @Translation("Single variation (Product information)"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = true
 * )
 */
class SingleVariationWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    // Do not allow this widget to be used as a default value widget.
    if ($this->isDefaultValueWidget($form_state)) {
      return $form;
    }

    return parent::form($items, $form, $form_state, $get_delta);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = [
      '#type' => 'details',
      '#open' => TRUE,
      // Remove the "required" cue, it's display-only and confusing.
      '#required' => FALSE,
      // Use a custom title for the widget because "Variations" doesn't make
      // sense in a single variation context.
      '#field_title' => $this->t('Product information'),
      '#after_build' => [
        [get_class($this), 'removeTranslatabilityClue'],
      ],
    ] + $element;

    $element['entity'] = [
      '#type' => 'inline_entity_form',
      '#entity_type' => 'commerce_product_variation',
      '#bundle' => reset($this->getFieldSetting('handler_settings')['target_bundles']),
      '#langcode' => $items->getEntity()->language()->getId(),
      '#default_value' => !$items->isEmpty() ? $items->first()->entity : NULL,
    ];

    return $element;
  }

  /**
   * After-build callback for removing the translatability clue from the widget.
   *
   * IEF expects the entity reference field to not be translatable, to avoid
   * different translations having different references.
   * However, that causes ContentTranslationHandler::addTranslatabilityClue()
   * to add an "(all languages)" suffix to the widget title. That suffix is
   * incorrect, since IEF does ensure that specific entity translations are
   * being edited.
   */
  public static function removeTranslatabilityClue(array $element, FormStateInterface $form_state) {
    $element['#title'] = $element['#field_title'];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    if ($this->isDefaultValueWidget($form_state)) {
      $items->filterEmptyItems();
      return;
    }

    $parents = array_merge($form['#parents'], [$this->fieldDefinition->getName(), 'widget']);
    $element = NestedArray::getValue($form, $parents);
    $variation = $element['entity']['#entity'];
    $values = [
      ['entity' => $variation],
    ];
    $items->setValue($values);
    $items->filterEmptyItems();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_product' && $field_name == 'variations';
  }

}
