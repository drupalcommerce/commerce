<?php

namespace Drupal\commerce_promotion\Element;

use Drupal\commerce\Element\CommerceElementTrait;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\commerce_order\Adjustment;

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
      // The coupon ID.
      '#default_value' => NULL,
      '#order_id' => NULL,
      '#multiple_coupons' => FALSE,
      '#process' => [
        [$class, 'processForm'],
      ],
      '#element_validate' => [
        [$class, 'validateForm'],
      ],
      '#element_ajax' => [],
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

    $has_coupons = !$order->get('coupons')->isEmpty();
    $element['coupons'] = CouponRedemptionForm::buildCouponsTable($element, $order);
    $element['code'] = [
      '#type' => 'textfield',
      '#title' => t('Coupon code'),
      '#description' => t('Enter your coupon code to redeem a promotion.'),
      '#access' => !$has_coupons || $element['#multiple_coupons'],
    ];
    $element['apply'] = [
      '#type' => 'submit',
      '#value' => t('Redeem'),
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
      '#access' => !$has_coupons || $element['#multiple_coupons'],
    ];
    return $element;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#parents'];
    array_pop($parents);
    $coupon_element = NestedArray::getValue($form, $parents);

    $response = new AjaxResponse();
    // To refresh the coupon.
    $response->addCommand(new InsertCommand(NULL, $coupon_element));
    // Ensure messages are attached.
    $response->addCommand(new PrependCommand(NULL, ['#type' => 'status_messages']));
    // Run additional attached AJAX handlers.
    foreach ($coupon_element['#element_ajax'] as $element_ajax) {
      if (is_callable($element_ajax)) {
        $command = $element_ajax($form, $form_state);
        if ($command instanceof CommandInterface) {
          $response->addCommand($command);
        }
      }
    }

    return $response;
  }

  /**
   * Apply coupon submit callback.
   */
  public static function applyCoupon(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#parents'];
    array_pop($parents);
    $element = NestedArray::getValue($form, $parents);

    // Clear the coupon code input.
    $user_input = &$form_state->getUserInput();
    unset($user_input[$parents[0]]);

    $entity_type_manager = \Drupal::entityTypeManager();
    $order_storage = $entity_type_manager->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($element['#order_id']);

    $coupon = $form_state->getValue($parents);
    $order->get('coupons')->appendItem($coupon);
    $order->save();
    $form_state->setRebuild();
    drupal_set_message(t('Coupon applied'));

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

    if (!empty($triggering_element['#coupon_code'])) {
      /** @var \Drupal\commerce_promotion\CouponStorageInterface $coupon_storage */
      $coupon_storage = $entity_type_manager->getStorage('commerce_promotion_coupon');
      $coupon_to_delete = $coupon_storage->loadByCode($triggering_element['#coupon_code']);

      // Find $coupon_to_delete id.
      $coupons = $order->get('coupons')->getValue();
      $coupons_ids = array_map(function ($coupon) {
        return $coupon['target_id'];
      }, $coupons);
      $coupon_id = array_search($coupon_to_delete->id(), $coupons_ids);
      $order->get('coupons')->removeItem($coupon_id);
    }
    else {
      $order->set('coupons', []);
    }
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
    $triggering_element = $form_state->getTriggeringElement();
    if (isset($triggering_element['#coupon_code'])) {
      return;
    }

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
      $form_state->setErrorByName($code_path, t('Coupon is invalid - a coupon code was not provided.'));
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

  /**
   * Adjustments table builder.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return array
   *   The element array.
   */
  public static function buildCouponsTable(array $element, OrderInterface $order) {
    /** @var \Drupal\commerce_order\Adjustment[] $adjustments */
    $adjustments = array_filter($order->collectAdjustments(), function (Adjustment $adjustment) {
      return $adjustment->getType() == 'promotion';
    });
    $promotion_ids = array_map(function (Adjustment $adjustment) {
      return $adjustment->getSourceId();
    }, $adjustments);

    /** @var \Drupal\commerce_promotion\Entity\CouponInterface[] $coupons */
    $coupons = $order->get('coupons')->referencedEntities();
    if (empty($coupons) || empty($adjustments)) {
      return [];
    }

    $table = [
      '#type' => 'table',
      '#header' => [
        ['data' => t('Promotion'), 'class' => ['invisible']],
        ['data' => t('Amount'), 'class' => ['invisible']],
        ['data' => t('Remove'), 'class' => ['invisible']],
      ],
      '#empty' => t('There are no promotions applied.'),
    ];

    // Use special format for promotion with coupon.
    /** @var \Drupal\commerce_promotion\Entity\CouponInterface $coupon */
    foreach ($coupons as $index => $coupon) {
      $adjustment_index = array_search($coupon->getPromotionId(), $promotion_ids);
      $adjustment = $adjustments[$adjustment_index];

      $label = t(':title (:code)', [
        ':title' => $coupon->getPromotion()->getName(),
        ':code' => $coupon->getCode(),
      ]);
      $table[$index]['label'] = [
        '#type' => 'inline_template',
        '#template' => '{{ label }}',
        '#context' => [
          'label' => $label,
        ],
      ];
      $table[$index]['amount'] = [
        '#type' => 'inline_template',
        '#template' => '{{ price|commerce_price_format }}',
        '#context' => [
          'price' => $adjustment->getAmount(),
        ],
      ];
      $table[$index]['remove'] = [
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
        // The remove coupon method expects the button to be directly under
        // the root element.
        '#parents' => array_merge($element['#parents'], ['remove_coupon_' . $index]),
        '#coupon_code' => $coupon->getCode(),
        '#submit' => [
          [get_called_class(), 'removeCoupon'],
        ],
      ];
    }
    return $table;
  }

}
