<?php

namespace Drupal\commerce_product\Form;

use Drupal\commerce_product\Entity\ProductAttributeInterface;
use Drupal\commerce_product\Entity\ProductAttributeValueInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides common functionality for the product attribute translation forms.
 */
trait ProductAttributeTranslationFormTrait {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The inline form manager.
   *
   * @var \Drupal\commerce\InlineFormManager
   */
  protected $inlineFormManager;

  /**
   * Builds the translation form for product attribute values.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\commerce_product\Entity\ProductAttributeInterface $attribute
   *   The product attribute.
   *
   * @return array
   *   The translation form.
   */
  protected function buildValuesForm(array $form, FormStateInterface $form_state, ProductAttributeInterface $attribute) {
    $values = $attribute->getValues();
    $has_translatable_values = FALSE;
    foreach ($values as $value) {
      if ($value->isTranslatable()) {
        $has_translatable_values = TRUE;
        break;
      }
    }
    // Don't display the values if there's nothing to translate.
    if (!$has_translatable_values) {
      return $form;
    }

    $language = $form_state->get('config_translation_language');
    $source_language = $form_state->get('config_translation_source_language');
    // Set the keys expected by the inline form.
    $form_state->set('langcode', $language->getId());
    $form_state->set('entity_default_langcode', $source_language->getId());

    $form['values'] = [
      '#type' => 'table',
      '#header' => [$this->t('Value'), $this->t('Value')],
      // #input defaults to TRUE, which breaks file fields on the value form.
      // This table is used for visual grouping only, the element itself
      // doesn't have any values of its own that need processing.
      '#input' => FALSE,
    ];
    foreach ($values as $index => $value) {
      $inline_form = $this->inlineFormManager->createInstance('content_entity', [], $value);
      $original_value = $value;
      if ($value->hasTranslation($source_language->getId())) {
        $original_value = $value->getTranslation($source_language->getId());
      }

      $value_form = &$form['values'][$index];
      $value_form['source'] = [
        'value' => $this->renderOriginalValue($original_value),
        '#wrapper_attributes' => ['style' => 'width: 50%'],
      ];
      $value_form['translation'] = [
        '#parents' => ['values', $index, 'translation'],
        '#wrapper_attributes' => ['style' => 'width: 50%'],
      ];
      $value_form['translation'] = $inline_form->buildInlineForm($value_form['translation'], $form_state);
    }

    return $form;
  }

  /**
   * Renders the given product attribute value in the original language.
   *
   * Skips non-translatable fields. Skips all base fields other than the name.
   *
   * @param \Drupal\commerce_product\Entity\ProductAttributeValueInterface $value
   *   The product attribute value.
   *
   * @return array
   *   The render array.
   */
  protected function renderOriginalValue(ProductAttributeValueInterface $value) {
    $view_builder = $this->entityTypeManager->getViewBuilder('commerce_product_variation');
    $build = [];
    foreach ($value->getFieldDefinitions() as $field_name => $definition) {
      if (!$definition->isTranslatable()) {
        continue;
      }
      if ($definition instanceof BaseFieldDefinition && $field_name != 'name') {
        continue;
      }

      $build[$field_name] = $view_builder->viewField($value->get($field_name), ['label' => 'above']);
    }

    return $build;
  }

}
