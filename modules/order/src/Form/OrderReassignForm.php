<?php

namespace Drupal\commerce_order\Form;

use Drupal\commerce_order\OrderAssignment;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\user\Entity\User;
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
   * The order assignment service.
   *
   * @var \Drupal\commerce_order\OrderAssignment
   */
  protected $order_assignment_service;

  /**
   * Constructs a new OrderReassignForm object.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   * @param \Drupal\commerce_order\OrderAssignment $order_assignment_service
   *   The order assignment service.
   */
  public function __construct(CurrentRouteMatch $current_route_match, OrderAssignment $order_assignment_service) {
    $this->order = $current_route_match->getParameter('commerce_order');
    $this->order_assignment_service = $order_assignment_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('commerce_order.order_assignment')
    );
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
    if (!$this->order->getCustomerId()) {
      $current_customer = $this->t('anonymous user with the email %email', [
        '%email' => $this->order->getEmail(),
      ]);
    }
    else {
      $customer = $this->order->getCustomer();
      // If the display name has been altered to not be the email address,
      // show the email as well.
      if ($customer->getDisplayName() != $customer->getEmail()) {
        $customer_link_text = $this->t('@display (@email)', [
          '@display' => $customer->getDisplayName(),
          '@email' => $customer->getEmail(),
        ]);
      }
      else {
        $customer_link_text = $customer->getDisplayName();
      }

      $current_customer = $this->order->getCustomer()->toLink($customer_link_text)->toString();
    }

    $form['current_customer'] = [
      '#type' => 'item',
      '#markup' => $this->t('The order is currently assigned to @customer.', [
        '@customer' => $current_customer,
      ]),
    ];
    $form += $this->buildCustomerForm($form, $form_state, $this->order);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reassign order'),
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

    $this->order_assignment_service->assign($this->order, User::load($values['uid']), TRUE);

    drupal_set_message($this->t('The order %label has been assigned to customer %customer.', [
      '%label' => $this->order->label(),
      '%customer' => $this->order->getCustomer()->label(),
    ]));
    $form_state->setRedirectUrl($this->order->toUrl('collection'));
  }

}
