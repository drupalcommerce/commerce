<?php

namespace Drupal\commerce_promotion\Element;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\PrependCommand;
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
 *   '#description' => t('Enter your coupon code to redeem a promotion.'),
 *   '#order_id' => $order_id,
 * ];
 * @endcode
 * The element takes the coupon list from $order->get('coupons').
 * The order is saved when a coupon is added or removed.
 *
 * @FormElement("commerce_coupon_redemption_form")
 */
class CouponRedemptionForm extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#element_ajax' => [],
      // If NULL, the cardinality is unlimited.
      '#cardinality' => 1,
      '#order_id' => NULL,

      '#title' => t('Coupon code'),
      '#description' => NULL,
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
   * Processes the coupon redemption form.
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

    $id_prefix = implode('-', $element['#parents']);
    // @todo We cannot use unique IDs, or multiple elements on a page currently.
    // @see https://www.drupal.org/node/2675688
    // $wrapper_id = Html::getUniqueId($id_prefix . '-ajax-wrapper');
    $wrapper_id = $id_prefix . '-ajax-wrapper';
    $coupons = $order->get('coupons')->referencedEntities();
    $cardinality_reached = $element['#cardinality'] && count($coupons) >= $element['#cardinality'];

    $element = [
      '#tree' => TRUE,
      '#theme' => 'commerce_coupon_redemption_form',
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      // Pass the id along to other methods.
      '#wrapper_id' => $wrapper_id,
    ] + $element;
    $element['code'] = [
      '#type' => 'textfield',
      '#title' => $element['#title'],
      '#description' => $element['#description'],
      '#access' => !$cardinality_reached,
    ];
    $element['apply'] = [
      '#type' => 'submit',
      '#value' => t('Apply coupon'),
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
      '#access' => !$cardinality_reached,
    ];
    $element = self::buildCouponOverview($element, $coupons);

    return $element;
  }

  /**
   * Builds an overview of applied coupons.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\commerce_promotion\Entity\CouponInterface[] $coupons
   *   The coupons.
   *
   * @return array
   *   The element array.
   */
  public static function buildCouponOverview(array $element, array $coupons) {
    if (empty($coupons)) {
      return $element;
    }

    foreach ($coupons as $index => $coupon) {
      $element['coupons'][$index]['code'] = [
        '#plain_text' => $coupon->getCode(),
      ];
      $element['coupons'][$index]['display_name'] = [
        // @todo Use the promotion display name once added.
        '#plain_text' => $coupon->getPromotion()->label(),
      ];
      $element['coupons'][$index]['remove_button'] = [
        '#type' => 'submit',
        '#value' => t('Remove coupon'),
        '#name' => 'remove_coupon_' . $index,
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefresh'],
          'wrapper' => $element['#wrapper_id'],
        ],
        '#weight' => 50,
        '#limit_validation_errors' => [
          $element['#parents'],
        ],
        '#coupon_id' => $coupon->id(),
        '#submit' => [
          [get_called_class(), 'removeCoupon'],
        ],
        // Simplify ajaxRefresh() by having all triggering elements
        // on the same level.
        '#parents' => array_merge($element['#parents'], ['remove_coupon_' . $index]),
      ];
    }

    return $element;
  }

  /**
   * Validates the coupon redemption element.
   *
   * Runs if the 'Apply coupon' button was clicked, or the main form
   * was submitted by the user clicking the primary submit button.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateForm(array &$element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $button_type = isset($triggering_element['#button_type']) ? $triggering_element['#button_type'] : NULL;
    if ($triggering_element['#name'] != 'apply_coupon' && $button_type != 'primary') {
      return;
    }

    $coupon_code_parents = array_merge($element['#parents'], ['code']);
    $coupon_code = $form_state->getValue($coupon_code_parents);
    $coupon_code_path = implode('][', $coupon_code_parents);
    if (empty($coupon_code)) {
      if ($triggering_element['#name'] == 'apply_coupon') {
        $form_state->setErrorByName($coupon_code_path, t('Please provide a coupon code.'));
      }
      return;
    }
    /** @var \Drupal\commerce_promotion\CouponStorageInterface $coupon_storage */
    $coupon_storage = \Drupal::entityTypeManager()->getStorage('commerce_promotion_coupon');
    $coupon = $coupon_storage->loadEnabledByCode($coupon_code);
    if (empty($coupon)) {
      $form_state->setErrorByName($coupon_code_path, t('The provided coupon code is invalid.'));
      return;
    }

    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($element['#order_id']);
    foreach ($order->get('coupons') as $item) {
      if ($item->target_id == $coupon->id()) {
        $form_state->setErrorByName($coupon_code_path, t('The provided coupon code is invalid.'));
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
    $element['code']['#coupon_id'] = $coupon->id();
  }

  /**
   * Submit callback for the "Apply coupon" button.
   */
  public static function applyCoupon(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $element = NestedArray::getValue($form, $parents);
    // Clear the coupon code input.
    $user_input = &$form_state->getUserInput();
    NestedArray::setValue($user_input, array_merge($parents, ['code']), '');

    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($element['#order_id']);
    $order->get('coupons')->appendItem($element['code']['#coupon_id']);
    $order->save();
    $form_state->setRebuild();
  }

  /**
   * Remove coupon submit callback.
   */
  public static function removeCoupon(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $element = NestedArray::getValue($form, $parents);

    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($element['#order_id']);
    $coupons_ids = array_map(function ($coupon) {
      return $coupon['target_id'];
    }, $order->get('coupons')->getValue());
    $coupon_index = array_search($triggering_element['#coupon_id'], $coupons_ids);
    $order->get('coupons')->removeItem($coupon_index);
    $order->save();
    $form_state->setRebuild();
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#parents'];
    array_pop($parents);
    $element = NestedArray::getValue($form, $parents);

    $response = new AjaxResponse();
    $response->addCommand(new InsertCommand(NULL, $element));
    $response->addCommand(new PrependCommand(NULL, ['#type' => 'status_messages']));
    // Allow parent elements to hook into the ajax refresh.
    foreach ($element['#element_ajax'] as $element_ajax) {
      if (is_callable($element_ajax)) {
        $command = $element_ajax($form, $form_state);
        if ($command instanceof CommandInterface) {
          $response->addCommand($command);
        }
      }
    }

    return $response;
  }

}
