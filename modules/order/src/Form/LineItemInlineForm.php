<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Form\LineItemInlineForm.
 */

namespace Drupal\commerce_order\Form;

use Drupal\commerce_order\Entity\LineItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Form\EntityInlineForm;

/**
 * Defines the inline form for line items.
 */
class LineItemInlineForm extends EntityInlineForm {

  /**
   * {@inheritdoc}
   */
  public function labels() {
    $labels = [
      'singular' => t('line item'),
      'plural' => t('line items'),
    ];
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function tableFields($bundles) {
    $fields = parent::tableFields($bundles);
    $fields['unit_price'] = [
      'type' => 'field',
      'label' => t('Unit price'),
      'weight' => 2,
    ];
    $fields['quantity'] = [
      'type' => 'field',
      'label' => t('Quantity'),
      'weight' => 3,
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm($entity_form, FormStateInterface $form_state) {
    $entity_form = parent::entityForm($entity_form, $form_state);
    $entity_form['#entity_builders'][] = [get_class($this), 'populateTitle'];

    return $entity_form;
  }

  /**
   * Entity builder: populates the line item title from the purchased entity.
   *
   * @param string $entity_type
   *   The entity type identifier.
   * @param \Drupal\commerce_order\Entity\LineItemInterface $line_item
   *   The line item.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function populateTitle($entity_type, LineItemInterface $line_item, array $form, FormStateInterface $form_state) {
    $purchased_entity = $line_item->getPurchasedEntity();
    if ($line_item->isNew() && $purchased_entity) {
      $line_item->setTitle($purchased_entity->getLineItemTitle());
    }
  }

}
