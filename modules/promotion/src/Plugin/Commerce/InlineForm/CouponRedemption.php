<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\InlineForm;

use Drupal\commerce\Plugin\Commerce\InlineForm\InlineFormBase;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an inline form for redeeming a coupon.
 *
 * @CommerceInlineForm(
 *   id = "coupon_redemption",
 *   label = @Translation("Coupon redemption"),
 * )
 */
class CouponRedemption extends InlineFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CouponRedemption object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // The order_id is passed via configuration to avoid serializing the
      // order, which is loaded from scratch in the submit handler to minimize
      // chances of a conflicting save.
      'order_id' => '',
      // NULL for unlimited.
      'max_coupons' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function requiredConfiguration() {
    return ['order_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildInlineForm(array $inline_form, FormStateInterface $form_state) {
    $inline_form = parent::buildInlineForm($inline_form, $form_state);

    $order = $this->entityTypeManager->getStorage('commerce_order')->load($this->configuration['order_id']);
    if (!$order) {
      throw new \RuntimeException('Invalid order_id given to the coupon_redemption inline form.');
    }
    assert($order instanceof OrderInterface);
    /** @var \Drupal\commerce_promotion\Entity\CouponInterface[] $coupons */
    $coupons = $order->get('coupons')->referencedEntities();

    $inline_form = [
      '#tree' => TRUE,
      '#attached' => [
        'library' => ['commerce_promotion/coupon_redemption_form'],
      ],
      '#theme' => 'commerce_coupon_redemption_form',
      '#configuration' => $this->getConfiguration(),
    ] + $inline_form;
    $inline_form['code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Coupon code'),
      // Chrome autofills this field with the address line 1, and ignores
      // autocomplete => 'off', but respects 'new-password'.
      '#attributes' => [
        'autocomplete' => 'new-password',
      ],
    ];
    $inline_form['apply'] = [
      '#type' => 'submit',
      '#value' => t('Apply coupon'),
      '#name' => 'apply_coupon',
      '#limit_validation_errors' => [
        $inline_form['#parents'],
      ],
      '#submit' => [
        [get_called_class(), 'applyCoupon'],
      ],
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxRefreshForm'],
        'element' => $inline_form['#parents'],
      ],
    ];
    $max_coupons = $this->configuration['max_coupons'];
    if ($max_coupons && count($coupons) >= $max_coupons) {
      // Don't allow additional coupons to be added.
      $inline_form['code']['#access'] = FALSE;
      $inline_form['apply']['#access'] = FALSE;
    }

    foreach ($coupons as $index => $coupon) {
      $inline_form['coupons'][$index]['code'] = [
        '#plain_text' => $coupon->getCode(),
      ];
      $inline_form['coupons'][$index]['display_name'] = [
        // @todo Use the promotion display name once added.
        '#plain_text' => $coupon->getPromotion()->label(),
      ];
      $inline_form['coupons'][$index]['remove_button'] = [
        '#type' => 'submit',
        '#value' => t('Remove coupon'),
        '#name' => 'remove_coupon_' . $index,
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefreshForm'],
          'element' => $inline_form['#parents'],
        ],
        '#weight' => 50,
        '#limit_validation_errors' => [
          $inline_form['#parents'],
        ],
        '#coupon_id' => $coupon->id(),
        '#submit' => [
          [get_called_class(), 'removeCoupon'],
        ],
        // Simplify ajaxRefresh() by having all triggering elements
        // on the same level.
        '#parents' => array_merge($inline_form['#parents'], ['remove_coupon_' . $index]),
      ];
    }

    return $inline_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateInlineForm(array &$inline_form, FormStateInterface $form_state) {
    parent::validateInlineForm($inline_form, $form_state);

    // Runs if the 'Apply coupon' button was clicked, or the main form
    // was submitted by the user clicking the primary submit button.
    $triggering_element = $form_state->getTriggeringElement();
    $button_type = isset($triggering_element['#button_type']) ? $triggering_element['#button_type'] : NULL;
    if ($triggering_element['#name'] != 'apply_coupon' && $button_type != 'primary') {
      return;
    }

    $coupon_code_parents = array_merge($inline_form['#parents'], ['code']);
    $coupon_code = $form_state->getValue($coupon_code_parents);
    $coupon_code_path = implode('][', $coupon_code_parents);
    if (empty($coupon_code)) {
      if ($triggering_element['#name'] == 'apply_coupon') {
        $form_state->setErrorByName($coupon_code_path, t('Please provide a coupon code.'));
      }
      return;
    }
    /** @var \Drupal\commerce_promotion\CouponStorageInterface $coupon_storage */
    $coupon_storage = $this->entityTypeManager->getStorage('commerce_promotion_coupon');
    $coupon = $coupon_storage->loadEnabledByCode($coupon_code);
    if (empty($coupon)) {
      $form_state->setErrorByName($coupon_code_path, t('The provided coupon code is invalid.'));
      return;
    }

    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($this->configuration['order_id']);
    foreach ($order->get('coupons') as $item) {
      if ($item->target_id == $coupon->id()) {
        // Coupon already applied. Error message not set for UX reasons.
        return;
      }
    }
    if (!$coupon->available($order)) {
      $form_state->setErrorByName($coupon_code_path, t('The provided coupon code is invalid.'));
      return;
    }
    if (!$coupon->getPromotion()->applies($order)) {
      $form_state->setErrorByName($coupon_code_path, t('The provided coupon code is invalid.'));
      return;
    }

    // Save the coupon ID for applyCoupon.
    $inline_form['code']['#coupon_id'] = $coupon->id();
  }

  /**
   * Submit callback for the "Apply coupon" button.
   */
  public static function applyCoupon(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $inline_form = NestedArray::getValue($form, $parents);
    // Clear the coupon code input.
    $user_input = &$form_state->getUserInput();
    NestedArray::setValue($user_input, array_merge($parents, ['code']), '');

    if (isset($inline_form['code']['#coupon_id'])) {
      $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
      /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
      $order = $order_storage->load($inline_form['#configuration']['order_id']);
      $order->get('coupons')->appendItem($inline_form['code']['#coupon_id']);
      $order->save();
    }
    $form_state->setRebuild();
  }

  /**
   * Submit callback for the "Remove coupon" button.
   */
  public static function removeCoupon(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $inline_form = NestedArray::getValue($form, $parents);

    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($inline_form['#configuration']['order_id']);
    $coupon_ids = array_column($order->get('coupons')->getValue(), 'target_id');
    $coupon_index = array_search($triggering_element['#coupon_id'], $coupon_ids);
    $order->get('coupons')->removeItem($coupon_index);
    $order->save();
    $form_state->setRebuild();
  }

}
