<?php

namespace Drupal\commerce_payment\Form;

use Drupal\commerce\InlineFormManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the payment operation form.
 */
class PaymentOperationForm extends EntityForm implements ContainerInjectionInterface {

  /**
   * The inline form manager.
   *
   * @var \Drupal\commerce\InlineFormManager
   */
  protected $inlineFormManager;

  /**
   * Constructs a new PaymentAddForm instance.
   *
   * @param \Drupal\commerce\InlineFormManager $inline_form_manager
   *   The inline form manager.
   */
  public function __construct(InlineFormManager $inline_form_manager) {
    $this->inlineFormManager = $inline_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_inline_form')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $operations = $payment_gateway_plugin->buildPaymentOperations($payment);
    $operation_id = $this->getRouteMatch()->getParameter('operation');
    $operation = $operations[$operation_id];
    $inline_form = $this->inlineFormManager->createInstance('payment_gateway_form', [
      'operation' => $operation['plugin_form'],
    ], $this->entity);

    $form['#title'] = $operation['page_title'];
    $form['#tree'] = TRUE;
    $form['payment'] = [
      '#parents' => ['payment'],
      '#inline_form' => $inline_form,
    ];
    $form['payment'] = $inline_form->buildInlineForm($form['payment'], $form_state);
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $operation['title'],
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button']],
      '#url' => $this->entity->toUrl('collection'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
    $inline_form = $form['payment']['#inline_form'];
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $inline_form->getEntity();

    if (!empty($form['payment']['#success_message'])) {
      $this->messenger()->addMessage($form['payment']['#success_message']);
    }
    $form_state->setRedirect('entity.commerce_payment.collection', ['commerce_order' => $payment->getOrderId()]);
  }

}
