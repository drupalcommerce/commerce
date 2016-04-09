<?php

namespace Drupal\commerce_product\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductAttributeOverviewForm extends FormBase {

  /**
   * The current product attribute.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeInterface
   */
  protected $attribute;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_product_attribute_overview';
  }

  /**
   * Constructs a new ProductAttributeOverviewForm object.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(CurrentRouteMatch $current_route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->attribute = $current_route_match->getParameter('commerce_product_attribute');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    $values = $this->attribute->getValues();
    // The value map allows new values to be added and removed before saving.
    // An array in the $index => $id format. $id is '_new' for unsaved values.
    $value_map = (array) $form_state->get('value_map');
    if (empty($value_map)) {
      $value_map = $values ? array_keys($values) : ['_new'];
      $form_state->set('value_map', $value_map);
    }
    // Remind the user for which product attribute the values are being managed.
    $form['#title'] = $this->attribute->label();

    $wrapper_id = Html::getUniqueId('product-attribute-values-ajax-wrapper');
    $form['values'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => $this->t('Value'), 'colspan' => 2],
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'product-attribute-value-order-weight',
        ],
      ],
      '#weight' => 5,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    foreach ($value_map as $index => $id) {
      $value_form = &$form['values'][$index];
      // The tabledrag element is always added to the first cell in the row,
      // so we add an empty cell to guide it there, for better styling.
      $value_form['#attributes']['class'][] = 'draggable';
      $value_form['tabledrag'] = [
        '#markup' => '',
      ];

      $value_form['entity'] = [
        '#type' => 'inline_entity_form',
        '#entity_type' => 'commerce_product_attribute_value',
        '#bundle' => $this->attribute->id(),
        '#save_entity' => FALSE,
      ];
      if ($id == '_new') {
        $default_weight = 999;
        $remove_access = TRUE;
      }
      else {
        $value = $values[$id];
        $value_form['entity']['#default_value'] = $value;
        $default_weight = $value->getWeight();
        $remove_access = $value->access('delete');
      }

      $value_form['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $default_weight,
        '#attributes' => [
          'class' => ['product-attribute-value-order-weight'],
        ],
      ];
      // Used by SortArray::sortByWeightProperty to sort the rows.
      if (isset($user_input['values'][$index])) {
        $value_form['#weight'] = $user_input['values'][$index]['weight'];
      }
      else {
        $value_form['#weight'] = $default_weight;
      }

      $value_form['remove'] = [
        '#type' => 'submit',
        '#name' => 'remove_value' . $index,
        '#value' => $this->t('Remove'),
        '#limit_validation_errors' => [],
        '#submit' => ['::removeValueSubmit'],
        '#value_index' => $index,
        '#ajax' => [
          'callback' => '::valuesAjax',
          'wrapper' => $wrapper_id,
        ],
        '#access' => $remove_access,
      ];
    }

    // Sort the values by weight. Ensures weight is preserved on ajax refresh.
    uasort($form['values'], ['\Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);

    $access_handler = $this->entityTypeManager->getAccessControlHandler('commerce_product_attribute_value');
    if ($access_handler->createAccess($this->attribute->id())) {
      $form['values']['_add_new'] = [
        '#tree' => FALSE,
      ];
      $form['values']['_add_new']['entity'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add'),
        '#submit' => ['::addValueSubmit'],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::valuesAjax',
          'wrapper' => $wrapper_id,
        ],
        '#prefix' => '<div class="product-attribute-value-new">',
        '#suffix' => '</div>',
      ];
      $form['values']['_add_new']['weight'] = [
        'data' => [],
      ];
      $form['values']['_add_new']['operations'] = [
        'data' => [],
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save values'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Ajax callback for value operations.
   */
  public function valuesAjax(array $form, FormStateInterface $form_state) {
    return $form['values'];
  }

  /**
   * Submit callback for adding a new value.
   */
  public function addValueSubmit(array $form, FormStateInterface $form_state) {
    $value_map = (array) $form_state->get('value_map');
    $value_map[] = '_new';
    $form_state->set('value_map', $value_map);
    $form_state->setRebuild();
  }

  /**
   * Submit callback for removing a value.
   */
  public function removeValueSubmit(array $form, FormStateInterface $form_state) {
    $value_index = $form_state->getTriggeringElement()['#value_index'];
    $value_map = (array) $form_state->get('value_map');
    $value_id = $value_map[$value_index];
    unset($value_map[$value_index]);
    $form_state->set('value_map', $value_map);
    // Non-new values also need to be deleted from storage.
    if ($value_id != '_new') {
      $delete_queue = (array) $form_state->get('delete_queue');
      $delete_queue[] = $value_id;
      $form_state->set('delete_queue', $delete_queue);
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $delete_queue = $form_state->get('delete_queue');
    if (!empty($delete_queue)) {
      $value_storage = $this->entityTypeManager->getStorage('commerce_product_attribute_value');
      $values = $value_storage->loadMultiple($delete_queue);
      $value_storage->delete($values);
    }

    foreach ($form_state->getValue(['values']) as $index => $value_data) {
      /** @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface $value */
      $value = $form['values'][$index]['entity']['#entity'];
      $value->setWeight($value_data['weight']);
      $value->save();
    }

    drupal_set_message($this->t('Saved the @attribute attribute values.', ['@attribute' => $this->attribute->label()]));
  }

}
