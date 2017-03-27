<?php

namespace Drupal\commerce_product\Plugin\Commerce\PromotionCondition;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_promotion\Plugin\Commerce\PromotionCondition\PromotionConditionBase;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Provides an 'Order: Total amount comparison' condition.
 *
 * @CommercePromotionCondition(
 *   id = "commerce_product_variation_field_equals",
 *   label = @Translation("Product Variation field equals"),
 *   target_entity_type = "commerce_order_item",
 * )
 */
class ProductVariationFieldEquals extends PromotionConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
             'bundle' => NULL,
             'field' => NULL,
             'value' => NULL,
           ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form += parent::buildConfigurationForm($form, $form_state);
    $ajax_wrapper_id = Html::getUniqueId('ajax-wrapper');
    // Prefix and suffix used for Ajax replacement.
    $form['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $form['#suffix'] = '</div>';

//    $entity_id = isset($this->configuration['entity']) ? $this->configuration['entity'] : NULL;
    $selected_bundle = isset($this->configuration['bundle']) ? $this->configuration['bundle'] : NULL;
    $bundles = \Drupal::service("entity_type.bundle.info")->getBundleInfo('commerce_product_variation');
    $bundle_options = [];
    foreach ($bundles as $bundle => $label) {
      $bundle_options[$bundle] = $label['label'];
    }

    $form['bundle'] = [
      '#type' => 'select',
      '#options' => $bundle_options,
      '#title' => t('Product variation bundle'),
      '#default_value' =>  $selected_bundle,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'bundleAjaxCallback'],
        'wrapper' => $ajax_wrapper_id,
      ],
    ];
    if (!$selected_bundle) {
      return $form;
    }

    $fields = \Drupal::service("entity_field.manager")->getFieldDefinitions('commerce_product_variation', $selected_bundle);
    $selected_field = isset($this->configuration['field']) ? $this->configuration['field'] : NULL;

    $filed_options = [];
    foreach ($fields as $field_id => $field_definition) {
      $filed_options[$field_id] = $field_definition->getLabel();
    }

    $form['field'] = [
      '#type' => 'select',
      '#title' => t('Field'),
      '#options' => $filed_options,
      '#default_value' =>  $selected_field,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'bundleAjaxCallback'],
        'wrapper' => $ajax_wrapper_id,
      ],
    ];

    if (!$selected_field) {
      return $form;
    }

    //Create an empty representative entity
    $commerce_product_variation = \Drupal::service('entity_type.manager')->getStorage('commerce_product_variation')->create(array(
        'type' => $selected_bundle,
        $selected_field => $this->configuration[$selected_field],
      )
    );

    //Get the EntityFormDisplay (i.e. the default Form Display) of this content type
    $entity_form_display = \Drupal::service('entity_type.manager')->getStorage('entity_form_display')
                                  ->load('commerce_product_variation.' . $selected_bundle . '.default');

    //Get the body field widget and add it to the form
    if ($widget = $entity_form_display->getRenderer($selected_field)) { //Returns the widget class
      $items = $commerce_product_variation->get($selected_field); //Returns the FieldItemsList interface
      $items->filterEmptyItems();
      $form[$selected_field] = $widget->form($items, $form, $form_state); //Builds the widget form and attach it to your form
      $form[$selected_field]['widget']['#required'] = TRUE;
    }

    return $form;
  }

  public function bundleAjaxCallback(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $form_element = NestedArray::getValue($form, $parents);
    return $form_element;
  }


  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $bundle_id = $this->configuration['bundle'];
    if (empty($bundle_id)) {
      return FALSE;
    }
    $field_id = $this->configuration['field'];
    if (empty($field_id)) {
      return FALSE;
    }
    /** @var OrderItemInterface $order_item */
    $order_item = $this->getContextValue('commerce_order_item');

    /** @var ProductVariationInterface $current_product */
    $current_product_variation = $order_item->getPurchasedEntity();
    if ($current_product_variation->bundle() != $bundle_id) {
      return FALSE;
    }

    if (!$current_product_variation->hasField($field_id)) {
      return FALSE;
    }

    $field_type = $current_product_variation->get($field_id)->getFieldDefinition()->getType();
    $target_type = NULL;
    if ($field_type == 'entity_reference') {
      $target_type = $current_product->get($field_id)->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type');
    }

    if ($target_type == 'taxonomy_term') {
      if ($current_product_variation->get($field_id)->getValue() == $this->configuration[$field_id]) {
        return TRUE;
      }
      else {
        /** @var TermInterface $term */
        $term = \Drupal::service('entity_type.manager')
                       ->getStorage("taxonomy_term")->load($this->configuration[$field_id][0]['target_id']);
        $tree = \Drupal::service('entity_type.manager')
                       ->getStorage("taxonomy_term")
                       ->loadTree($term->getVocabularyId(), $term->id());
        $found = FALSE;
        foreach ($tree as $item) {
          if ($item->tid == $current_product_variation->get($field_id)->getValue()[0]['target_id']) {
            $found = TRUE;
            break;
          }
        }
        return $found;
      }
    }
    elseif ($current_product_variation->get($field_id)->getValue() != $this->configuration[$field_id]) {
      return FALSE;
    }

    return TRUE;

  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Compares the product variation entity.');
  }

}
