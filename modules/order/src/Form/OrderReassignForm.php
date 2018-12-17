<?php

namespace Drupal\commerce_order\Form;

use Drupal\commerce_order\OrderAssignmentInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * The order assignment service.
   *
   * @var \Drupal\commerce_order\OrderAssignmentInterface
   */
  protected $orderAssignment;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new OrderReassignForm object.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   * @param \Drupal\commerce_order\OrderAssignmentInterface $order_assignment
   *   The order assignment service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(CurrentRouteMatch $current_route_match, OrderAssignmentInterface $order_assignment, EntityTypeManagerInterface $entity_type_manager) {
    $this->order = $current_route_match->getParameter('commerce_order');
    $this->orderAssignment = $order_assignment;
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('commerce_order.order_assignment'),
      $container->get('entity_type.manager')
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
    $customer = $this->order->getCustomer();
    if ($customer->isAnonymous()) {
      $current_customer = $this->t('anonymous user with the email %email', [
        '%email' => $this->order->getEmail(),
      ]);
    }
    else {
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
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($values['uid']);
    $this->orderAssignment->assign($this->order, $user);
    $this->messenger()->addMessage($this->t('The order %label has been assigned to customer %customer.', [
      '%label' => $this->order->label(),
      '%customer' => $this->order->getCustomer()->label(),
    ]));
    $form_state->setRedirectUrl($this->order->toUrl('collection'));
  }

}
