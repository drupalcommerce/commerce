<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\commerce_order\OrderRefreshInterface;
use Drupal\commerce_promotion\Form\CouponRedemptionForm;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the billing information pane.
 *
 * @CommerceCheckoutPane(
 *   id = "coupon",
 *   label = "Coupons",
 *   default_step = "order_information",
 *   wrapper_element = "fieldset",
 * )
 */
class Coupon extends CheckoutPaneBase implements CheckoutPaneInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The order type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderTypeStorage;

  /**
   * The order type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $promotionCouponStorage;

  /**
   * The order type storage.
   *
   * @var \Drupal\commerce_promotion\PromotionStorageInterface
   */
  protected $promotionStorage;

  /**
   * The order refresh.
   *
   * @var \Drupal\commerce_order\OrderRefreshInterface
   */
  protected $orderRefresh;

  /**
   * Constructs a new BillingInformation object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_order\OrderRefreshInterface $order_refresh
   *   The order refresh.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, OrderRefreshInterface $order_refresh) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow);
    $this->entityTypeManager = $entity_type_manager;
    $this->orderTypeStorage = $this->entityTypeManager->getStorage('commerce_order_type');
    $this->promotionCouponStorage = $this->entityTypeManager->getStorage('commerce_promotion_coupon');
    $this->promotionStorage = $this->entityTypeManager->getStorage('commerce_promotion');
    $this->orderRefresh = $order_refresh;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager'),
      $container->get('commerce_order.order_refresh')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    return $this->buildAdjustmentsTable(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {

    // Prepare the form for ajax.
    // Not using Html::getUniqueId() on the wrapper ID to avoid #2675688.
    $pane_form['#wrapper_id'] = 'coupon-redemption-wrapper';
    $pane_form['#prefix'] = '<div id="' . $pane_form['#wrapper_id'] . '">';
    $pane_form['#suffix'] = '</div>';

    $pane_form['code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Coupon code'),
      '#description' => $this->t('Enter your coupon code here.'),
    ];
    $pane_form['apply'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply coupon'),
      '#name' => 'apply_coupon',
      '#limit_validation_errors' => [
        array_merge($pane_form['#parents'], ['code']),
      ],
      '#ajax' => [
        'wrapper' => $pane_form['#wrapper_id'],
        'callback' => [get_class($this), 'ajaxRefresh'],
      ],
    ];
    $pane_form['table'] = $this->buildAdjustmentsTable($pane_form, $form_state);
    return $pane_form;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    return NestedArray::getValue($form, $parents);
  }

  public static function ajaxCouponRemoveRefresh(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -3);
    return NestedArray::getValue($form, $parents);
  }

  /**
   * Ajax callback: coupon add button.
   */
  public static function removeCouponCallback($form, FormStateInterface &$form_state) {
    $order_refresh = \Drupal::service('commerce_order.order_refresh');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $form_state->get('order');
    list($source_type, $source_id) = explode(':', $form_state->getTriggeringElement()['#parents'][2]);
    foreach ($order->get('coupons') as $index => $item) {
      if ($item->entity->id() == $source_id && $item->entity->getEntityTypeId() == $source_type) {
        $order->get('coupons')->removeItem($index);
        break;
      }
    }
    $order_refresh->refresh($order);
    $order->save();

    return $form;
  }

  /**
   * Adjustments table builder.
   *
   * @param array $pane_form
   *   The pane form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the parent form.
   * @param bool $hide_actions
   *   TRUE if actions hidden.
   *
   * @return array
   *   A renderable array.
   */
  protected function buildAdjustmentsTable($pane_form, FormStateInterface $form_state, $hide_actions = FALSE) {
    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        // $this->t('Amount')
      ],
      '#empty' => $this->t('You have not redeemed any coupons.'),
    ];
    if (!$hide_actions) {
      $table['#header'][] = $this->t('Remove');
    }

    /** @var \Drupal\commerce_promotion\Entity\CouponInterface[] $coupons */
    $coupons = $this->order->get('coupons')->referencedEntities();

    if (empty($coupons)) {
      return $table;
    }

    foreach ($coupons as  $coupon) {
      // @todo add back reference from coupon to promotion.

      $coupon_id = $coupon->id();
      $table[$coupon_id]['label'] = [
        '#plain_text' => $coupon->label(),
      ];
      // $table[$coupon_id]['amount'] = [
      //  '#type' => 'inline_template',
      //  '#template' => '{{ price|commerce_price_format }}',
      //  '#context' => [
      //    'price' => $adjustment->getAmount(),
      //  ],
      // ];
      if (!$hide_actions) {
        $table[$coupon_id]['remove'] = [
          '#type' => 'button',
          '#value' => $this->t('Remove coupon'),
          '#name' => 'remove_coupon',
          '#coupon_id' => $coupon_id,
          '#attributes' => [
            'class' => [
              'delete-button',
              'delete-coupon-button',
            ],
          ],
          '#limit_validation_errors' => [],
          '#ajax' => [
            'wrapper' => $pane_form['#wrapper_id'],
            'callback' => [get_class($this), 'ajaxCouponRemoveRefresh'],
          ],
        ];
      }
    }

    return $table;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $coupon_parents = array_merge($pane_form['#parents'], ['code']);
    $coupon_code = $form_state->getValue($coupon_parents);
    if (empty($coupon_code)) {
      return;
    }
    $coupon = $this->promotionCouponStorage->loadByProperties(['code' => $coupon_code]);
    if (empty($coupon)) {
      $code_path = implode('][', $coupon_parents);
      $form_state->setErrorByName($code_path, $this->t('Coupon is invalid'));
    }
    else {
      $coupon = reset($coupon);
      /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
      $order_type = $this->orderTypeStorage->load($this->order->bundle());
      $promotion = $this->promotionStorage->loadByCoupon($order_type, $this->order->getStore(), $coupon);
      if (empty($promotion)) {
        $code_path = implode('][', $coupon_parents);
        $form_state->setErrorByName($code_path, $this->t('Coupon is invalid'));
      }
    }
    // @todo validate if coupon added yet.
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#name'] == 'apply_coupon') {
      $coupon_parents = array_merge($pane_form['#parents'], ['code']);
      $coupon_code = $form_state->getValue($coupon_parents);
      $coupons = $this->promotionCouponStorage->loadByProperties(['code' => $coupon_code]);
      if (!empty($coupons)) {
        $coupon = reset($coupons);
        /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
        $order_type = $this->orderTypeStorage->load($this->order->bundle());
        /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
        $promotion = $this->promotionStorage->loadByCoupon($order_type, $this->order->getStore(), $coupon);
        if ($promotion) {
          if ($promotion->applies($this->order)) {
            $this->order->get('coupons')->appendItem($coupon);
          }
          else {
            foreach ($this->order->getItems() as $orderItem) {
              if ($promotion->applies($orderItem)) {
                $this->order->get('coupons')->appendItem($coupon);
              }
            }
          }
          $this->orderRefresh->refresh($this->order);
          $this->order->save();
          drupal_set_message($this->t('Coupon applied'));
        }
        else {
          drupal_set_message($this->t('Coupon could not be applied'), 'error');
        }
      }
    }
    elseif ($triggering_element['#name'] == 'remove_coupon') {
      $coupon_id = $triggering_element['#coupon_id'];
      /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
      foreach ($this->order->get('coupons') as $offset => $item) {
        if ($item->target_id == $coupon_id) {
          $this->order->get('coupons')->removeItem($offset);
          break;
        }
      }
      $this->orderRefresh->refresh($this->order);
      $this->order->save();
      drupal_set_message($this->t('Coupon removed'));
    }
  }


}
