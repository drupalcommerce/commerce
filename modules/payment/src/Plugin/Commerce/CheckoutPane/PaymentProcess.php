<?php

namespace Drupal\commerce_payment\Plugin\Commerce\CheckoutPane;

use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the payment process pane.
 *
 * @CommerceCheckoutPane(
 *   id = "payment_process",
 *   label = @Translation("Payment process"),
 *   default_step = "payment",
 *   wrapper_element = "container",
 * )
 */
class PaymentProcess extends CheckoutPaneBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PaymentProcess object.
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow);

    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'capture' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    if (!empty($this->configuration['capture'])) {
      $summary = $this->t('Transaction mode: Authorize and capture');
    }
    else {
      $summary = $this->t('Transaction mode: Authorize only');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['capture'] = [
      '#type' => 'radios',
      '#title' => $this->t('Transaction mode'),
      '#description' => $this->t('This setting is only respected if the chosen payment gateway supports authorizations.'),
      '#options' => [
        TRUE => $this->t('Authorize and capture'),
        FALSE => $this->t('Authorize only (requires manual capture after checkout)'),
      ],
      '#default_value' => $this->configuration['capture'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['capture'] = !empty($values['capture']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    // The payment gateway is currently always required to be set.
    if ($this->order->get('payment_gateway')->isEmpty()) {
      drupal_set_message($this->t('No payment gateway selected.'), 'error');
      $this->redirectToPreviousStep();
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->order->payment_gateway->entity;
    $payment_gateway_plugin = $payment_gateway->getPlugin();

    if ($payment_gateway_plugin instanceof OnsitePaymentGatewayInterface) {
      try {
        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
        $payment = $payment_storage->create([
          'state' => 'new',
          'amount' => $this->order->getTotalPrice(),
          'payment_gateway' => $payment_gateway->id(),
          'payment_method' => $this->order->payment_method->entity,
          'order_id' => $this->order->id(),
        ]);
        $payment_gateway_plugin->createPayment($payment, $this->configuration['capture']);

        $next_step_id = $this->checkoutFlow->getNextStepId();
        // @todo Add a checkout flow method for completing checkout.
        if ($next_step_id == 'complete') {
          $transition = $this->order->getState()->getWorkflow()->getTransition('place');
          $this->order->getState()->applyTransition($transition);
        }
        $this->order->checkout_step = $next_step_id;
        $this->order->save();

        throw new NeedsRedirectException(Url::fromRoute('commerce_checkout.form', [
          'commerce_order' => $this->order->id(),
          'step' => $next_step_id,
        ])->toString());

      }
      catch (PaymentGatewayException $e) {
        drupal_set_message($e->getMessage(), 'error');
        $this->redirectToPreviousStep();
      }
    }
    else {
      drupal_set_message($this->t('Sorry, we can currently only support on site payment gateways.'), 'error');
      $this->redirectToPreviousStep();
    }
  }

  /**
   * Redirects to a previous checkout step on error.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   */
  protected function redirectToPreviousStep() {
    $previous_step_id = $this->checkoutFlow->getPreviousStepId();
    foreach ($this->checkoutFlow->getPanes() as $pane) {
      if ($pane->getId() == 'payment_information') {
        $previous_step_id = $pane->getStepId();
      }
    }

    $this->order->checkout_step = $previous_step_id;
    $this->order->save();
    throw new NeedsRedirectException(Url::fromRoute('commerce_checkout.form', [
      'commerce_order' => $this->order->id(),
      'step' => $previous_step_id,
    ])->toString());
  }

}
