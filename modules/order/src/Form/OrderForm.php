<?php

namespace Drupal\commerce_order\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the commerce_order entity edit forms.
 */
class OrderForm extends ContentEntityForm {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new OrderForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->entity;
    $form = parent::form($form, $form_state);

    $form['#tree'] = TRUE;
    $form['#theme'] = 'commerce_order_edit_form';
    $form['#attached']['library'][] = 'commerce_order/form';
    // Changed must be sent to the client, for later overwrite error checking.
    $form['changed'] = [
      '#type' => 'hidden',
      '#default_value' => $order->getChangedTime(),
    ];

    $last_saved = $this->dateFormatter->format($order->getChangedTime(), 'short');
    $form['advanced'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['entity-meta']],
      '#weight' => 99,
    ];
    $form['meta'] = [
      '#attributes' => ['class' => ['entity-meta__header']],
      '#type' => 'container',
      '#group' => 'advanced',
      '#weight' => -100,
      'state' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $order->getState()->getLabel(),
        '#attributes' => [
          'class' => ['entity-meta__title'],
        ],
        // Hide the rendered state if there's a widget for it.
        '#access' => empty($form['store_id']),
      ],
      'date' => NULL,
      'changed' => $this->fieldAsReadOnly($this->t('Last saved'), $last_saved),
    ];
    $form['customer'] = [
      '#type' => 'details',
      '#title' => t('Customer information'),
      '#group' => 'advanced',
      '#open' => TRUE,
      '#attributes' => [
        'class' => ['order-form-author'],
      ],
      '#weight' => 91,
    ];

    if ($placed_time = $order->getPlacedTime()) {
      $date = $this->dateFormatter->format($placed_time, 'short');
      $form['meta']['date'] = $this->fieldAsReadOnly($this->t('Placed'), $date);
    }
    // Show the order's store only if there are multiple available.
    $store_query = $this->entityTypeManager->getStorage('commerce_store')->getQuery();
    $store_count = $store_query->count()->execute();
    if ($store_count > 1) {
      $store_link = $order->getStore()->toLink()->toString();
      $form['meta']['store'] = $this->fieldAsReadOnly($this->t('Store'), $store_link);
    }
    // Move uid/mail widgets to the sidebar, or provide read-only alternatives.
    $customer = $order->getCustomer();
    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'customer';
    }
    elseif ($customer->isAuthenticated()) {
      $customer_link = $customer->toLink()->toString();
      $form['customer']['uid'] = $this->fieldAsReadOnly($this->t('Customer'), $customer_link);
    }
    if (isset($form['mail'])) {
      $form['mail']['#group'] = 'customer';
    }
    elseif (!empty($order->getEmail())) {
      $form['customer']['mail'] = $this->fieldAsReadOnly($this->t('Contact email'), $order->getEmail());
    }
    // All additional customer information should come after uid/mail.
    $form['customer']['ip_address'] = $this->fieldAsReadOnly($this->t('IP address'), $order->getIpAddress());

    return $form;
  }

  /**
   * Builds a read-only form element for a field.
   *
   * @param string $label
   *   The element label.
   * @param string $value
   *   The element value.
   *
   * @return array
   *   The form element.
   */
  protected function fieldAsReadOnly($label, $value) {
    return [
      '#type' => 'item',
      '#wrapper_attributes' => [
        'class' => ['container-inline'],
      ],
      '#title' => $label,
      '#markup' => $value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->messenger()->addMessage($this->t('The order %label has been successfully saved.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_order.canonical', ['commerce_order' => $this->entity->id()]);
  }

}
