<?php

namespace Drupal\commerce_payment\Form;

use Drupal\commerce\InlineFormManager;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the payment method edit form.
 */
class PaymentMethodEditForm extends EntityForm implements ContainerInjectionInterface {

  /**
   * The inline form manager.
   *
   * @var \Drupal\commerce\InlineFormManager
   */
  protected $inlineFormManager;

  /**
   * Constructs a new PaymentMethodEditForm instance.
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
    $inline_form = $this->inlineFormManager->createInstance('payment_gateway_form', [
      'operation' => 'edit-payment-method',
    ], $this->entity);

    $form['payment_method'] = [
      '#parents' => ['payment_method'],
      '#inline_form' => $inline_form,
    ];
    $form['payment_method'] = $inline_form->buildInlineForm($form['payment_method'], $form_state);
    $form['actions'] = $this->actionsElement($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
    $inline_form = $form['payment_method']['#inline_form'];
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $inline_form->getEntity();

    $this->messenger()->addMessage($this->t('Saved the %label payment method.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirectUrl($payment_method->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // The entity was saved by the inline form.
  }

}
