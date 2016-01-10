<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Form\OrderReassignForm.
 */

namespace Drupal\commerce_order\Form;

use Drupal\commerce_order\Form\CustomerFormTrait;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for assigning orders to a different customer.
 */
class OrderReassignForm extends FormBase {

  use CustomerFormTrait;

  /**
   * The current order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Constructs a new OrderReassignForm object.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   */
  public function __construct(CurrentRouteMatch $current_route_match) {
    $this->order = $current_route_match->getParameter('commerce_order');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('current_route_match'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_order_reassign_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = $this->buildCustomerForm($form, $form_state, $this->order);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Reassign order'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->submitCustomerForm($form, $form_state);

    $values = $form_state->getValues();
    $this->order->setEmail($values['mail']);
    $this->order->setOwnerId($values['uid']);
    $this->order->save();
    drupal_set_message($this->t('The order %label has been assigned to customer %customer.', [
      '%label' => $this->order->label(),
      '%customer' => $this->order->getOwner()->label(),
    ]));
    $form_state->setRedirectUrl($this->order->toUrl('collection'));
  }

}
