<?php

namespace Drupal\commerce_promotion\Element;

use Drupal\commerce\Element\CommerceElementTrait;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for redeeming a coupon.
 *
 * Usage example:
 * @code
 * $form['coupon'] = [
 *   '#type' => 'commerce_coupon_redemption_form',
 *   '#title' => t('Coupon code'),
 *   '#default_value' => $coupon_id,
 *   '#order_id' => $order_id,
 * ];
 * @endcode
 * The element value ($form_state->getValue('coupon')) will be the
 * coupon ID. Note that the order is not saved if the element was
 * submitted as a result of the main form being submitted. It is the
 * responsibility of the caller to update the order in that case.
 *
 * @FormElement("commerce_coupon_redemption_form")
 */
class CouponRedemptionForm extends FormElement {

  use CommerceElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#title' => t('Coupon code'),
      '#description' => t('Enter your coupon code here.'),
      '#submit_title' => t('Apply coupon'),
      '#submit_message' => t('Coupon applied'),
      '#remove_title' => t('Remove coupon'),
      // The coupon ID.
      '#default_value' => NULL,
      '#order_id' => NULL,
      '#process' => [
        [$class, 'processForm'],
      ],
      '#element_validate' => [
        [$class, 'validateForm'],
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
   *   Thrown when the #order_id property is empty or invalid.
   *
   * @return array
   *   The processed form element.
   */
  public static function processForm(array $element, FormStateInterface $form_state, array &$complete_form) {
    if (empty($element['#order_id'])) {
      throw new \InvalidArgumentException('The commerce_coupon_redemption_form element requires the #order_id property.');
    }
    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    $order = $order_storage->load($element['#order_id']);
    if (!$order instanceof OrderInterface) {
      throw new \InvalidArgumentException('The commerce_coupon_redemption #order_id must be a valid order ID.');
    }

    $has_coupons = !$order->get('coupons')->isEmpty();
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
      '#access' => !$has_coupons,
    ];
    $element['apply'] = [
      '#type' => 'submit',
      '#value' => $element['#submit_title'],
      '#name' => 'apply_coupon',
      '#limit_validation_errors' => [
        $element['#parents'],
      ],
      '#submit' => [
        [get_called_class(), 'applyCoupon'],
      ],
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxRefresh'],
        'wrapper' => $element['#wrapper_id'],
      ],
      '#access' => !$has_coupons,
    ];
    $element['remove'] = [
      '#type' => 'submit',
      '#value' => $element['#remove_title'],
      '#name' => 'remove_coupon',
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxRefresh'],
        'wrapper' => $element['#wrapper_id'],
      ],
      '#weight' => 50,
      '#limit_validation_errors' => [
        $element['#parents'],
      ],
      '#submit' => [
        [get_called_class(), 'removeCoupon'],
      ],
      '#access' => $has_coupons,
    ];

    return $element;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#parents'];
    array_pop($parents);
    return NestedArray::getValue($form, $parents);
  }

  /**
   * Apply coupon submit callback.
   */
  public static function applyCoupon(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#parents'];
    array_pop($parents);
    $element = NestedArray::getValue($form, $parents);

    $entity_type_manager = \Drupal::entityTypeManager();
    $order_storage = $entity_type_manager->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($element['#order_id']);

    $coupon = $form_state->getValue($parents);
    $order->get('coupons')->appendItem($coupon);
    $order->save();
    $form_state->setRebuild();
    drupal_set_message($element['#submit_message']);
  }

  /**
   * Remove coupon submit callback.
   */
  public static function removeCoupon(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#parents'];
    array_pop($parents);
    $element = NestedArray::getValue($form, $parents);

    $entity_type_manager = \Drupal::entityTypeManager();
    $order_storage = $entity_type_manager->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($element['#order_id']);

    $order->get('coupons')->setValue([]);
    $order->save();
    $form_state->setRebuild();
  }

  /**
   * Validates the coupon redemption element.
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

    $order_storage = $entity_type_manager->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($element['#order_id']);
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
    if (!$coupon->available($order)) {
      $form_state->setErrorByName($code_path, t('Coupon is invalid'));
      return;
    }
    if (!$coupon->getPromotion()->applies($order)) {
      $form_state->setErrorByName($code_path, t('Coupon is invalid'));
      return;
    }

    $form_state->setValueForElement($element, $coupon);
  }

}
