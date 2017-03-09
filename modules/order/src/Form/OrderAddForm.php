<?php

namespace Drupal\commerce_order\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the order add form.
 */
class OrderAddForm extends FormBase {

  use CustomerFormTrait;

  /**
   * The order storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $orderStorage;

  /**
   * The store storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $storeStorage;

  /**
   * Constructs a new OrderAddForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
    $this->storeStorage = $entity_type_manager->getStorage('commerce_store');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_order_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Skip building the form if there are no available stores.
    $store_query = $this->storeStorage->getQuery();
    if ($store_query->count()->execute() == 0) {
      $link = Link::createFromRoute('Add a new store.', 'entity.commerce_store.add_page');
      $form['warning'] = [
        '#markup' => $this->t("Orders can't be created until a store has been added. @link", ['@link' => $link->toString()]),
      ];
      return $form;
    }

    $form['type'] = [
      '#type' => 'commerce_entity_select',
      '#title' => $this->t('Order type'),
      '#target_type' => 'commerce_order_type',
      '#required' => TRUE,
    ];
    $form['store_id'] = [
      '#type' => 'commerce_entity_select',
      '#title' => $this->t('Store'),
      '#target_type' => 'commerce_store',
      '#required' => TRUE,
    ];
    $form = $this->buildCustomerForm($form, $form_state);

    $form['custom_placed_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Place the order on a different date'),
      '#default_value' => FALSE,
    ];
    // The datetime element needs to be wrapped in order for #states to work
    // properly. See https://www.drupal.org/node/2419131
    $form['placed_wrapper'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="custom_placed_date"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['placed_wrapper']['placed'] = [
      '#type' => 'datetime',
      '#title_display' => 'invisible',
      '#title' => $this->t('Placed'),
      '#default_value' => new DrupalDateTime(),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create'),
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
    $order_data = [
      'type' => $values['type'],
      'mail' => $values['mail'],
      'uid' => [$values['uid']],
      'store_id' => [$values['store_id']],
    ];
    if (!empty($values['custom_placed_date']) && !empty($values['placed'])) {
      $order_data['placed'] = $values['placed']->getTimestamp();
    }
    $order = $this->orderStorage->create($order_data);
    $order->save();
    // Redirect to the edit form to complete the order.
    $form_state->setRedirect('entity.commerce_order.edit_form', ['commerce_order' => $order->id()]);
  }

}
