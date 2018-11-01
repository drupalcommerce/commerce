<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

use Drupal\commerce\ConditionGroup;
use Drupal\commerce\ConditionManagerInterface;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\PriceSplitterInterface;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the "Buy X Get Y" offer for orders.
 *
 * Examples:
 * - "Buy 1 t-shirt, get 1 hat for $10 less"
 * - "Buy 3 t-shirts, get 2 t-shirts free (100% off)"
 *
 * The cheapest items are always discounted first. The offer applies multiple
 * times ("Buy 3 Get 1" will discount 2 items when 6 are bought).
 *
 * Decimal quantities are supported.
 *
 * @CommercePromotionOffer(
 *   id = "order_buy_x_get_y",
 *   label = @Translation("Buy X Get Y"),
 *   entity_type = "commerce_order",
 * )
 */
class BuyXGetY extends OrderPromotionOfferBase {

  /**
   * The condition manager.
   *
   * @var \Drupal\commerce\ConditionManagerInterface
   */
  protected $conditionManager;

  /**
   * Constructs a new BuyXGetY object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The pluginId for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The rounder.
   * @param \Drupal\commerce_order\PriceSplitterInterface $splitter
   *   The splitter.
   * @param \Drupal\commerce\ConditionManagerInterface $condition_manager
   *   The condition manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RounderInterface $rounder, PriceSplitterInterface $splitter, ConditionManagerInterface $condition_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $rounder, $splitter);

    $this->conditionManager = $condition_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_price.rounder'),
      $container->get('commerce_order.price_splitter'),
      $container->get('plugin.manager.commerce_condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'buy_quantity' => 1,
      'buy_conditions' => [],
      'get_quantity' => 1,
      'get_conditions' => [],
      'offer_type' => 'percentage',
      'offer_percentage' => '0',
      'offer_amount' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form += parent::buildConfigurationForm($form, $form_state);
    // Remove the main fieldset.
    $form['#type'] = 'container';

    $form['buy'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Customer buys'),
      '#collapsible' => FALSE,
    ];
    $form['buy']['quantity'] = [
      '#type' => 'commerce_number',
      '#title' => $this->t('Quantity'),
      '#default_value' => $this->configuration['buy_quantity'],
    ];
    $form['buy']['conditions'] = [
      '#type' => 'commerce_conditions',
      '#title' => $this->t('Matching any of the following'),
      '#parent_entity_type' => 'commerce_promotion',
      '#entity_types' => ['commerce_order_item'],
      '#default_value' => $this->configuration['buy_conditions'],
    ];

    $form['get'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Customer gets'),
      '#collapsible' => FALSE,
    ];
    $form['get']['quantity'] = [
      '#type' => 'commerce_number',
      '#title' => $this->t('Quantity'),
      '#default_value' => $this->configuration['get_quantity'],
    ];
    $form['get']['conditions'] = [
      '#type' => 'commerce_conditions',
      '#title' => $this->t('Matching any of the following'),
      '#parent_entity_type' => 'commerce_promotion',
      '#entity_types' => ['commerce_order_item'],
      '#default_value' => $this->configuration['get_conditions'],
    ];

    $parents = array_merge($form['#parents'], ['offer', 'type']);
    $selected_offer_type = NestedArray::getValue($form_state->getUserInput(), $parents);
    $selected_offer_type = $selected_offer_type ?: $this->configuration['offer_type'];
    $offer_wrapper = Html::getUniqueId('buy-x-get-y-offer-wrapper');
    $form['offer'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('At a discounted value'),
      '#collapsible' => FALSE,
      '#prefix' => '<div id="' . $offer_wrapper . '">',
      '#suffix' => '</div>',
    ];
    $form['offer']['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Discounted by a'),
      '#title_display' => 'invisible',
      '#options' => [
        'percentage' => $this->t('Percentage'),
        'fixed_amount' => $this->t('Fixed amount'),
      ],
      '#default_value' => $selected_offer_type,
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxRefresh'],
        'wrapper' => $offer_wrapper,
      ],
    ];
    if ($selected_offer_type == 'percentage') {
      $form['offer']['percentage'] = [
        '#type' => 'commerce_number',
        '#title' => $this->t('Percentage off'),
        '#default_value' => Calculator::multiply($this->configuration['offer_percentage'], '100'),
        '#maxlength' => 255,
        '#min' => 0,
        '#max' => 100,
        '#size' => 4,
        '#field_suffix' => $this->t('%'),
        '#required' => TRUE,
      ];
    }
    else {
      $form['offer']['amount'] = [
        '#type' => 'commerce_price',
        '#title' => $this->t('Amount off'),
        '#default_value' => $this->configuration['offer_amount'],
        '#required' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    $parents = array_slice($parents, 0, -2);
    return NestedArray::getValue($form, $parents);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    if ($values['offer']['type'] == 'percentage' && empty($values['offer']['percentage'])) {
      $form_state->setError($form, $this->t('Percentage must be a positive number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['buy_quantity'] = $values['buy']['quantity'];
      $this->configuration['buy_conditions'] = $values['buy']['conditions'];
      $this->configuration['get_quantity'] = $values['get']['quantity'];
      $this->configuration['get_conditions'] = $values['get']['conditions'];
      $this->configuration['offer_type'] = $values['offer']['type'];
      if ($this->configuration['offer_type'] == 'percentage') {
        $this->configuration['offer_percentage'] = Calculator::divide((string) $values['offer']['percentage'], '100');
        $this->configuration['offer_amount'] = NULL;
      }
      else {
        $this->configuration['offer_percentage'] = NULL;
        $this->configuration['offer_amount'] = $values['offer']['amount'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function apply(EntityInterface $entity, PromotionInterface $promotion) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    $order_items = $order->getItems();

    $buy_conditions = $this->buildConditionGroup($this->configuration['buy_conditions']);
    $buy_order_items = $this->selectOrderItems($order_items, $buy_conditions, 'DESC');
    $buy_quantities = array_map(function (OrderItemInterface $order_item) {
      return $order_item->getQuantity();
    }, $buy_order_items);
    if (array_sum($buy_quantities) < $this->configuration['buy_quantity']) {
      return;
    }

    $get_conditions = $this->buildConditionGroup($this->configuration['get_conditions']);
    $get_order_items = $this->selectOrderItems($order_items, $get_conditions, 'ASC');
    $get_quantities = array_map(function (OrderItemInterface $order_item) {
      return $order_item->getQuantity();
    }, $get_order_items);
    if (empty($get_quantities)) {
      return;
    }

    // It is possible for $buy_quantities and $get_quantities to overlap (have
    // the same order item IDs). For example, in a "Buy 3 Get 1" scenario with
    // a single T-shirt order item of quantity: 8, there are 6 bought and 2
    // discounted products, in this order: 3, 1, 3, 1. To ensure the specified
    // results, $buy_quantities must be processed group by group, with any
    // overlaps immediately removed from the $get_quantities (and vice-versa).
    $final_quantities = [];
    while (!empty($buy_quantities)) {
      $selected_buy_quantities = $this->sliceQuantities($buy_quantities, $this->configuration['buy_quantity']);
      if (array_sum($selected_buy_quantities) < $this->configuration['buy_quantity']) {
        break;
      }
      $get_quantities = $this->removeQuantities($get_quantities, $selected_buy_quantities);
      $selected_get_quantities = $this->sliceQuantities($get_quantities, $this->configuration['get_quantity']);
      $buy_quantities = $this->removeQuantities($buy_quantities, $selected_get_quantities);
      // Merge the selected get quantities into a final list, to ensure that
      // each order item only gets a single adjustment.
      $final_quantities = $this->mergeQuantities($final_quantities, $selected_get_quantities);
    }

    foreach ($final_quantities as $order_item_id => $quantity) {
      $order_item = $get_order_items[$order_item_id];
      $adjustment_amount = $this->buildAdjustmentAmount($order_item, $quantity);

      $order_item->addAdjustment(new Adjustment([
        'type' => 'promotion',
        // @todo Change to label from UI when added in #2770731.
        'label' => t('Discount'),
        'amount' => $adjustment_amount->multiply('-1'),
        'source_id' => $promotion->id(),
      ]));
    }
  }

  /**
   * Builds a condition group for the given condition configuration.
   *
   * @param array $condition_configuration
   *   The condition configuration.
   *
   * @return \Drupal\commerce\ConditionGroup
   *   The condition group.
   */
  protected function buildConditionGroup(array $condition_configuration) {
    $conditions = [];
    foreach ($condition_configuration as $condition) {
      if (!empty($condition['plugin'])) {
        $conditions[] = $this->conditionManager->createInstance($condition['plugin'], $condition['configuration']);
      }
    }

    return new ConditionGroup($conditions, 'OR');
  }

  /**
   * Selects non-free order items that match the given conditions.
   *
   * Selected order items are sorted by unit price in the specified direction.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface[] $order_items
   *   The order items.
   * @param \Drupal\commerce\ConditionGroup $conditions
   *   The conditions.
   * @param string $sort_direction
   *   The sort direction.
   *   Use 'ASC' for least expensive first, 'DESC' for most expensive first.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface[]
   *   The selected order items, keyed by order item ID.
   */
  protected function selectOrderItems(array $order_items, ConditionGroup $conditions, $sort_direction = 'ASC') {
    $selected_order_items = [];
    foreach ($order_items as $index => $order_item) {
      if ($order_item->getAdjustedTotalPrice()->isZero()) {
        continue;
      }
      if (!$conditions->evaluate($order_item)) {
        continue;
      }
      $selected_order_items[$order_item->id()] = $order_item;
    }
    uasort($selected_order_items, function (OrderItemInterface $a, OrderItemInterface $b) use ($sort_direction) {
      if ($sort_direction == 'ASC') {
        $result = $a->getUnitPrice()->compareTo($b->getUnitPrice());
      }
      else {
        $result = $b->getUnitPrice()->compareTo($a->getUnitPrice());
      }
      // PHP5 workaround, maintain existing sort order when items are equal.
      if ($result === 0) {
        $result = ($a->id() < $b->id()) ? -1 : 1;
      }

      return $result;
    });

    return $selected_order_items;
  }

  /**
   * Takes a slice from the given quantity list.
   *
   * For example, ['1' => '10', '2' => '5'] sliced for total quantity '11'
   * will produce a ['1' => '10', '2' => '1'] slice, leaving ['2' => '4']
   * in the original list.
   *
   * @param array $quantities
   *   The quantity list. Modified by reference.
   * @param string $total_quantity
   *   The total quantity of the new slice.
   *
   * @return array
   *   The quantity list slice.
   */
  protected function sliceQuantities(array &$quantities, $total_quantity) {
    $remaining_quantity = $total_quantity;
    $slice = [];
    foreach ($quantities as $order_item_id => $quantity) {
      if ($quantity <= $remaining_quantity) {
        $remaining_quantity = Calculator::subtract($remaining_quantity, $quantity);
        $slice[$order_item_id] = $quantity;
        unset($quantities[$order_item_id]);
        if ($remaining_quantity === '0') {
          break;
        }
      }
      else {
        $slice[$order_item_id] = $remaining_quantity;
        $quantities[$order_item_id] = Calculator::subtract($quantity, $remaining_quantity);
        break;
      }
    }

    return $slice;
  }

  /**
   * Removes the second quantity list from the first quantity list.
   *
   * For example: ['1' => '10', '2' => '20'] - ['1' => '10', '2' => '17']
   * will result in ['2' => '3'].
   *
   * @param array $first_quantities
   *   The first quantity list.
   * @param array $second_quantities
   *   The second quantity list.
   *
   * @return array
   *   The new quantity list.
   */
  protected function removeQuantities(array $first_quantities, array $second_quantities) {
    foreach ($second_quantities as $order_item_id => $quantity) {
      if (isset($first_quantities[$order_item_id])) {
        $first_quantities[$order_item_id] = Calculator::subtract($first_quantities[$order_item_id], $second_quantities[$order_item_id]);
        if ($first_quantities[$order_item_id] <= 0) {
          unset($first_quantities[$order_item_id]);
        }
      }
    }

    return $first_quantities;
  }

  /**
   * Merges the first quantity list with the second quantity list.
   *
   * Quantities belonging to shared order item IDs will be added together.
   *
   * For example: ['1' => '10'] and ['1' => '10', '2' => '17']
   * will merge into ['1' => '20', '2' => '17'].
   *
   * @param array $first_quantities
   *   The first quantity list.
   * @param array $second_quantities
   *   The second quantity list.
   *
   * @return array
   *   The new quantity list.
   */
  protected function mergeQuantities(array $first_quantities, array $second_quantities) {
    foreach ($second_quantities as $order_item_id => $quantity) {
      if (!isset($first_quantities[$order_item_id])) {
        $first_quantities[$order_item_id] = $quantity;
      }
      else {
        $first_quantities[$order_item_id] = Calculator::add($first_quantities[$order_item_id], $second_quantities[$order_item_id]);
      }
    }

    return $first_quantities;
  }

  /**
   * Builds an adjustment amount for the given order item and quantity.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param string $quantity
   *   The quantity.
   *
   * @return \Drupal\commerce_price\Price
   *   The adjustment amount.
   */
  protected function buildAdjustmentAmount(OrderItemInterface $order_item, $quantity) {
    if ($this->configuration['offer_type'] == 'percentage') {
      $percentage = (string) $this->configuration['offer_percentage'];
      $total_price = $order_item->getTotalPrice();
      if ($order_item->getQuantity() != $quantity) {
        // Calculate a new total for just the quantity that will be discounted.
        $total_price = $order_item->getUnitPrice()->multiply($quantity);
        $total_price = $this->rounder->round($total_price);
      }
      $adjustment_amount = $total_price->multiply($percentage);
    }
    else {
      $amount = Price::fromArray($this->configuration['offer_amount']);
      $adjustment_amount = $amount->multiply($quantity);
    }
    $adjustment_amount = $this->rounder->round($adjustment_amount);

    return $adjustment_amount;
  }

}
