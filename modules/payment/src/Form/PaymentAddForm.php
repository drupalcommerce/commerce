<?php

namespace Drupal\commerce_payment\Form;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides the payment add form.
 */
class PaymentAddForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Constructs a new PaymentMethodAddForm instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match) {
    $this->entityTypeManager = $entity_type_manager;
    $this->order = $route_match->getParameter('commerce_order');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_payment_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Prepare the form for ajax.
    $form['#wrapper_id'] = Html::getUniqueId('payment-add-form-wrapper');
    $form['#prefix'] = '<div id="' . $form['#wrapper_id'] . '">';
    $form['#suffix'] = '</div>';
    $form['#tree'] = TRUE;

    $step = $form_state->get('step');
    $step = $step ?: 'payment_method';
    $form_state->set('step', $step);
    if ($step == 'payment_method') {
      $form = $this->buildPaymentMethodForm($form, $form_state);
    }
    elseif ($step == 'payment') {
      $form = $this->buildPaymentForm($form, $form_state);
    }

    return $form;
  }

  /**
   * Builds the form for selecting a payment method.
   *
   * @param array $form
   *   The parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The built form.
   */
  protected function buildPaymentMethodForm(array $form, FormStateInterface $form_state) {
    // @todo
    // Support adding payments to anonymous orders, by adding support for
    // creating payment methods directly on this form.
    if (!$this->order->getCustomerId()) {
      throw new AccessDeniedHttpException();
    }

    /** @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $payment_gateway_storage */
    $payment_gateway_storage = $this->entityTypeManager->getStorage('commerce_payment_gateway');
    $payment_gateways = $payment_gateway_storage->loadMultipleForOrder($this->order);
    // Filter out payment gateways that don't support storing payment methods.
    $payment_gateways = array_filter($payment_gateways, function ($payment_gateway) {
      return $payment_gateway->getPlugin() instanceof SupportsStoredPaymentMethodsInterface;
    });
    // @todo Move this check to the access handler.
    if (count($payment_gateways) < 1) {
      throw new AccessDeniedHttpException();
    }

    $user_input = $form_state->getUserInput();
    $first_payment_gateway = reset($payment_gateways);
    $selected_payment_gateway_id = $first_payment_gateway->id();
    if (isset($user_input['payment_gateway'])) {
      $selected_payment_gateway_id = $user_input['payment_gateway'];
    }
    $selected_payment_gateway = $payment_gateways[$selected_payment_gateway_id];
    if (count($payment_gateways) > 1) {
      $payment_gateway_options = array_map(function ($payment_gateway) {
        /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
        return $payment_gateway->label();
      }, $payment_gateways);

      $form['payment_gateway'] = [
        '#type' => 'radios',
        '#title' => $this->t('Payment gateway'),
        '#options' => $payment_gateway_options,
        '#default_value' => $selected_payment_gateway_id,
        '#required' => TRUE,
        '#ajax' => [
          'callback' => [get_class($this), 'ajaxRefresh'],
          'wrapper' => $form['#wrapper_id'],
        ],
      ];
    }
    else {
      $form['payment_gateway'] = [
        '#type' => 'hidden',
        '#value' => $selected_payment_gateway_id,
      ];
    }

    /** @var \Drupal\commerce_payment\PaymentMethodStorageInterface $payment_method_storage */
    $payment_method_storage = $this->entityTypeManager->getStorage('commerce_payment_method');
    $payment_methods = $payment_method_storage->loadReusable($this->order->getCustomer(), $selected_payment_gateway);

    if (!empty($payment_methods)) {
      $selected_payment_method = reset($payment_methods);
      $payment_method_options = [];
      foreach ($payment_methods as $id => $payment_method) {
        $payment_method_options[$id] = $payment_method->label();
        if ($payment_method->isDefault()) {
          $selected_payment_method = $payment_method;
        }
      }

      $form['payment_method'] = [
        '#type' => 'radios',
        '#title' => $this->t('Payment method'),
        '#options' => $payment_method_options,
        '#default_value' => $selected_payment_method->id(),
        '#required' => TRUE,
        '#after_build' => [
          [get_class($this), 'clearValue'],
        ],
      ];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Continue'),
        '#button_type' => 'primary',
      ];
    }
    else {
      $form['payment_method'] = [
        '#type' => 'markup',
        '#markup' => $this->t('There are no reusable payment methods available'),
      ];
    }

    return $form;
  }

  /**
   * Clears the payment method value when the payment gateway changes.
   *
   * Changing the payment gateway results in a new set of payment methods,
   * causing the submitted value to trigger an "Illegal choice" error, cause
   * it's no longer allowed. Clearing the value causes the element to fallback
   * to the default value, avoiding the error.
   */
  public static function clearValue(array $element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (!isset($element['#options'][$value])) {
      $element['#value'] = NULL;
      $user_input = &$form_state->getUserInput();
      unset($user_input['payment_method']);
    }
    return $element;
  }

  /**
   * Builds the form for adding a payment.
   *
   * @param array $form
   *   The parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The built form.
   */
  protected function buildPaymentForm(array $form, FormStateInterface $form_state) {
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'payment_gateway' => $form_state->getValue('payment_gateway'),
      'payment_method' => $form_state->getValue('payment_method'),
      'order_id' => $this->order->id(),
    ]);

    $form['payment'] = [
      '#type' => 'commerce_payment_gateway_form',
      '#operation' => 'add-payment',
      '#default_value' => $payment,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add payment'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step');
    if ($step == 'payment_method') {
      $form_state->set('payment_gateway', $form_state->getValue('payment_gateway'));
      $form_state->set('payment_method', $form_state->getValue('payment_method_type'));
      $form_state->set('step', 'payment');
      $form_state->setRebuild(TRUE);
    }
    elseif ($step == 'payment') {
      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
      $payment = $form_state->getValue('payment');
      drupal_set_message($this->t('Payment saved.'));
      $form_state->setRedirect('entity.commerce_payment.collection', ['commerce_order' => $payment->getOrderId()]);
    }
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form;
  }

}
