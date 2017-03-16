<?php

namespace Drupal\commerce_promotion\Element;

use Drupal\commerce\Element\CommerceElementBase;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_promotion\Entity\CouponInterface;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form element for embedding the coupon redemption form.
 *
 * Usage example:
 * @code
 * $form['coupons'] = [
 *   '#type' => 'commerce_coupon_redemption_form',
 *   // The order to which the coupon will be applied to.
 *   '#order' => $order,
 * ];
 * @endcode
 *
 * @RenderElement("commerce_coupon_redemption_form")
 */
class CouponRedemptionForm extends CommerceElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      // The order to which the coupon will be applied to.
      '#order' => NULL,
      '#title' => t('Coupon code'),
      '#description' => t('Enter your coupon code here.'),
      '#submit_title' => t('Apply coupon'),
      '#process' => [
        [$class, 'attachElementSubmit'],
        [$class, 'processForm'],
      ],
      '#element_validate' => [
        [$class, 'validateElementSubmit'],
        [$class, 'validateForm'],
      ],
      '#commerce_element_submit' => [
        [$class, 'submitForm'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Builds the coupon redemption form.
   *
   * @param array $element
   *   The form element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the #order property is empty or invalid entity.
   *
   * @return array
   *   The processed form element.
   */
  public static function processForm(array $element, FormStateInterface $form_state, array &$complete_form) {
    if (empty($element['#order'])) {
      throw new \InvalidArgumentException('The commerce_coupon_redemption_form element requires the #order property.');
    }
    if (!$element['#order'] instanceof OrderInterface) {
      throw new \InvalidArgumentException('The commerce_coupon_redemption_form #order property must be an order entity.');
    }

    $id_prefix = implode('-', $element['#parents']);
    // @todo We cannot use unique IDs, or multiple elements on a page currently.
    // @see https://www.drupal.org/node/2675688
    // $wrapper_id = Html::getUniqueId($id_prefix . '-ajax-wrapper');
    $wrapper_id = $id_prefix . '-ajax-wrapper';

    $element = [
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      // Pass the id along to other methods.
      '#wrapper_id' => $wrapper_id,
    ] + $element;
    $element['code'] = [
      '#type' => 'textfield',
      '#title' => $element['#title'],
      '#description' => $element['#description'],
    ];
    $element['apply'] = [
      '#type' => 'submit',
      '#value' => $element['#submit_title'],
      '#name' => 'apply_coupon',
      '#limit_validation_errors' => [
        array_merge($element['#parents'], ['code']),
      ],
    ];

    return $element;
  }

  /**
   * Validates the coupon redemption form.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateForm(array &$element, FormStateInterface $form_state) {
    $coupon_parents = array_merge($element['#parents'], ['code']);
    $coupon_code = $form_state->getValue($coupon_parents);
    if (empty($coupon_code)) {
      return;
    }
    $entity_type_manager = \Drupal::entityTypeManager();
    $code_path = implode('][', $coupon_parents);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity_type_manager->getStorage('commerce_order')->load($element['#order']->id());

    /** @var \Drupal\commerce_promotion\CouponStorageInterface $coupon_storage */
    $coupon_storage = $entity_type_manager->getStorage('commerce_promotion_coupon');
    $coupon = $coupon_storage->loadByCode($coupon_code);
    if (empty($coupon)) {
      $form_state->setErrorByName($code_path, t('Coupon is invalid'));
      return;
    }

    foreach ($order->get('coupons') as $item) {
      if ($item->target_id == $coupon->id()) {
        $form_state->setErrorByName($code_path, t('Coupon has already been redeemed'));
        return;
      }
    }

    $order_type_storage = $entity_type_manager->getStorage('commerce_order_type');
    /** @var \Drupal\commerce_promotion\PromotionStorageInterface $promotion_storage */
    $promotion_storage = $entity_type_manager->getStorage('commerce_promotion');

    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $order_type_storage->load($order->bundle());
    $promotion = $promotion_storage->loadByCoupon($order_type, $order->getStore(), $coupon);
    if (empty($promotion)) {
      $form_state->setErrorByName($code_path, t('Coupon is invalid'));
      return;
    }

    if (!self::couponApplies($order, $promotion, $coupon)) {
      $form_state->setErrorByName($code_path, t('Coupon is invalid'));
      return;
    }
  }

  /**
   * Submits the coupon redemption form.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitForm(array &$element, FormStateInterface $form_state) {
    $coupon_parents = array_merge($element['#parents'], ['code']);
    $coupon_code = $form_state->getValue($coupon_parents);
    $entity_type_manager = \Drupal::entityTypeManager();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity_type_manager->getStorage('commerce_order')->load($element['#order']->id());
    $order_type_storage = $entity_type_manager->getStorage('commerce_order_type');
    /** @var \Drupal\commerce_promotion\PromotionStorageInterface $promotion_storage */
    $promotion_storage = $entity_type_manager->getStorage('commerce_promotion');
    /** @var \Drupal\commerce_promotion\CouponStorageInterface $coupon_storage */
    $coupon_storage = $entity_type_manager->getStorage('commerce_promotion_coupon');

    $coupon = $coupon_storage->loadByCode($coupon_code);
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $order_type_storage->load($order->bundle());
    $promotion = $promotion_storage->loadByCoupon($order_type, $order->getStore(), $coupon);

    if (self::couponApplies($order, $promotion, $coupon)) {
      $order->get('coupons')->appendItem($coupon);
      $order->save();
      drupal_set_message(t('Coupon applied'));
    }
  }

  /**
   * Checks if a coupon applies.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface $promotion
   *   The promotion.
   * @param \Drupal\commerce_promotion\Entity\CouponInterface $coupon
   *   The coupon.
   *
   * @return bool
   *   Returns TRUE if the coupon applies, FALSE otherwise.
   */
  protected static function couponApplies(OrderInterface $order, PromotionInterface $promotion, CouponInterface $coupon) {
    if ($promotion->applies($order)) {
      return TRUE;
    }
    else {
      foreach ($order->getItems() as $orderItem) {
        if ($promotion->applies($orderItem)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

}
