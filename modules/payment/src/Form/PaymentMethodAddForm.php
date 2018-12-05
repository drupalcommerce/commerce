<?php

namespace Drupal\commerce_payment\Form;

use Drupal\commerce\InlineFormManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides the payment method add form.
 */
class PaymentMethodAddForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The inline form manager.
   *
   * @var \Drupal\commerce\InlineFormManager
   */
  protected $inlineFormManager;

  /**
   * Constructs a new PaymentMethodAddForm instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce\InlineFormManager $inline_form_manager
   *   The inline form manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, InlineFormManager $inline_form_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->inlineFormManager = $inline_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_inline_form')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_payment_method_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    $payment_gateway = $form_state->get('payment_gateway');
    if (!$payment_gateway) {
      /** @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $payment_gateway_storage */
      $payment_gateway_storage = $this->entityTypeManager->getStorage('commerce_payment_gateway');
      $payment_gateway = $payment_gateway_storage->loadForUser($user);
      // @todo Move this check to the access handler.
      if (!$payment_gateway || !($payment_gateway->getPlugin() instanceof SupportsStoredPaymentMethodsInterface)) {
        throw new AccessDeniedHttpException();
      }
      $form_state->set('payment_gateway', $payment_gateway);
    }
    $step = $form_state->get('step');
    if (!$step) {
      $step = 'payment_method_type';
      // Skip the payment method type selection if there's only 1 type.
      $payment_method_types = $payment_gateway->getPlugin()->getPaymentMethodTypes();
      if (count($payment_method_types) === 1) {
        /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeInterface $payment_method_type */
        $payment_method_type = reset($payment_method_types);
        $form_state->set('payment_method_type', $payment_method_type->getPluginId());
        $step = 'payment_method';
      }
      $form_state->set('step', $step);
    }

    if ($step == 'payment_method_type') {
      $form = $this->buildPaymentMethodTypeForm($form, $form_state);
    }
    elseif ($step == 'payment_method') {
      $form = $this->buildPaymentMethodForm($form, $form_state);
    }

    return $form;
  }

  /**
   * Builds the form for selecting a payment method type.
   *
   * @param array $form
   *   The parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The built form.
   */
  protected function buildPaymentMethodTypeForm(array $form, FormStateInterface $form_state) {
    $payment_method_types = $form_state->get('payment_gateway')->getPlugin()->getPaymentMethodTypes();
    $payment_method_type_options = array_map(function ($payment_method_type) {
      /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeInterface $payment_method_type */
      return $payment_method_type->getLabel();
    }, $payment_method_types);

    $form['payment_method_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Payment method type'),
      '#options' => $payment_method_type_options,
      '#default_value' => '',
      '#required' => TRUE,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Builds the form for adding a payment method.
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
    $payment_method_storage = $this->entityTypeManager->getStorage('commerce_payment_method');
    $payment_method = $payment_method_storage->create([
      'type' => $form_state->get('payment_method_type'),
      'payment_gateway' => $form_state->get('payment_gateway'),
      'uid' => $form_state->getBuildInfo()['args'][0]->id(),
    ]);
    $inline_form = $this->inlineFormManager->createInstance('payment_gateway_form', [
      'operation' => 'add-payment-method',
    ], $payment_method);

    $form['payment_method'] = [
      '#parents' => ['payment_method'],
      '#inline_form' => $inline_form,
    ];
    $form['payment_method'] = $inline_form->buildInlineForm($form['payment_method'], $form_state);
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step');
    if ($step == 'payment_method_type') {
      $form_state->set('payment_method_type', $form_state->getValue('payment_method_type'));
      $form_state->set('step', 'payment_method');
      $form_state->setRebuild(TRUE);
    }
    elseif ($step == 'payment_method') {
      /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
      $inline_form = $form['payment_method']['#inline_form'];
      /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
      $payment_method = $inline_form->getEntity();
      $this->messenger()->addMessage($this->t('%label saved to your payment methods.', ['%label' => $payment_method->label()]));
      $form_state->setRedirect('entity.commerce_payment_method.collection', ['user' => $payment_method->getOwnerId()]);
    }
  }

}
