<?php

namespace Drupal\commerce_order\Plugin\Field\FieldWidget;

use Drupal\commerce\Context;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Form\OrderForm;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'commerce_unit_price' widget.
 *
 * @FieldWidget(
 *   id = "commerce_unit_price",
 *   label = @Translation("Unit price"),
 *   field_types = {
 *     "commerce_price",
 *   }
 * )
 */
class UnitPriceWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The chain price resolver.
   *
   * @var \Drupal\commerce_price\Resolver\ChainPriceResolverInterface
   */
  protected $chainPriceResolver;

  /**
   * Constructs a new UnitPriceWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\commerce_price\Resolver\ChainPriceResolverInterface $price_resolver
   *   The chain price resolver.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ChainPriceResolverInterface $price_resolver) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->chainPriceResolver = $price_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('commerce_price.chain_price_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'require_confirmation' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['require_confirmation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require confirmation before overriding the unit price'),
      '#default_value' => $this->getSetting('require_confirmation'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($this->getSetting('require_confirmation') == 1) {
      $summary[] = $this->t('Require confirmation before overriding the unit price');
    }
    else {
      $summary[] = $this->t('Do not require confirmation before overriding the unit price');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $items[$delta]->getEntity();
    if ($this->getSetting('require_confirmation')) {
      $element['override'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Override the unit price'),
        '#default_value' => $order_item->isUnitPriceOverridden(),
      ];
    }

    $element['amount'] = [
      '#type' => 'commerce_price',
      '#title' => $this->fieldDefinition->getLabel(),
      '#available_currencies' => array_filter($this->getFieldSetting('available_currencies')),
    ];
    if (!$items[$delta]->isEmpty()) {
      $element['amount']['#default_value'] = $items[$delta]->toPrice()->toArray();
    }
    if ($this->getSetting('require_confirmation')) {
      $checkbox_parents = array_merge($form['#parents'], [$this->fieldDefinition->getName(), 0, 'override']);
      $checkbox_path = array_shift($checkbox_parents);
      $checkbox_path .= '[' . implode('][', $checkbox_parents) . ']';

      $element['amount']['#states'] = [
        'visible' => [
          ':input[name="' . $checkbox_path . '"]' => ['checked' => TRUE],
        ],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $path = array_merge($form['#parents'], [$field_name, 0]);
    $values = NestedArray::getValue($form_state->getValues(), $path);
    $order_item = $items->getEntity();
    assert($order_item instanceof OrderItemInterface);
    $unit_price = NULL;

    if (!empty($values['override']) || !$this->getSetting('require_confirmation')) {
      // Verify the passed number was numeric before trying to set it.
      try {
        $unit_price = Price::fromArray($values['amount']);
        $order_item->setUnitPrice($unit_price, TRUE);
      }
      catch (\InvalidArgumentException $e) {
        $form_state->setErrorByName(implode('][', $path), $this->t('%title must be a number.', [
          '%title' => $this->fieldDefinition->getLabel(),
        ]));
      }
    }
    // If this is a new order item, resolve a default unit price.
    elseif ($order_item->isNew()) {
      $purchased_entity = $order_item->getPurchasedEntity();
      if ($purchased_entity !== NULL) {
        $order = $order_item->getOrder();
        if ($order === NULL) {
          $form_object = $form_state->getFormObject();
          assert($form_object instanceof OrderForm);
          $order = $form_object->getEntity();
          assert($order instanceof OrderInterface);
        }
        $context = new Context($order->getCustomer(), $order->getStore());
        $unit_price = $this->chainPriceResolver->resolve($purchased_entity, $order_item->getQuantity(), $context);
        $order_item->setUnitPrice($unit_price, FALSE);
      }
    }
    // Put delta mapping in $form_state, so that flagErrors() can use it.
    $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
    foreach ($items as $delta => $item) {
      $field_state['original_deltas'][$delta] = $delta;
    }
    static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type === 'commerce_order_item' && $field_name === 'unit_price';
  }

}
