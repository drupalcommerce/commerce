<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides the base checkout flow class.
 *
 * Checkout flows should extend this class only if they don't want to use
 * checkout panes. Otherwise they should extend CheckoutFlowWithPanesBase.
 */
abstract class CheckoutFlowBase extends PluginBase implements CheckoutFlowInterface, ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The current order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The current step ID.
   *
   * @var string
   */
  protected $stepId;

  /**
   * Constructs a new CheckoutFlowBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->order = $route_match->getParameter('commerce_order');
    // The order is empty when the checkout flow is initialized outside of the
    // checkout form (usually in the checkout flow admin UI). There's no need
    // to determine the current step ID in that case, it won't be used.
    if ($this->order) {
      $this->stepId = $this->processStepId($route_match->getParameter('step'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('current_route_match')
    );
  }

  /**
   * Processes the requested step ID.
   *
   * @param string $requested_step_id
   *   The step ID.
   *
   * @return string
   *   The processed step ID.
   */
  protected function processStepId($requested_step_id) {
    $step_ids = array_keys($this->getVisibleSteps());
    $step_id = $requested_step_id;
    if (empty($step_id) || !in_array($step_id, $step_ids)) {
      // Take the step ID from the order, or default to the first one.
      $step_id = $this->order->checkout_step->value;
      if (empty($step_id)) {
        $step_id = reset($step_ids);
      }
    }

    return $step_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * {@inheritdoc}
   */
  public function getStepId() {
    return $this->stepId;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousStepId() {
    $step_ids = array_keys($this->getVisibleSteps());
    $current_index = array_search($this->stepId, $step_ids);
    return isset($step_ids[$current_index - 1]) ? $step_ids[$current_index - 1] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStepId() {
    $step_ids = array_keys($this->getVisibleSteps());
    $current_index = array_search($this->stepId, $step_ids);
    return isset($step_ids[$current_index + 1]) ? $step_ids[$current_index + 1] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSteps() {
    // Each checkout flow plugin defines its own steps.
    // These two steps are always expected to be present.
    return [
      'offsite_payment' => [
        'label' => $this->t('Payment'),
        'next_label' => $this->t('Continue to payment'),
        'has_order_summary' => FALSE,
      ],
      'complete' => [
        'label' => $this->t('Complete'),
        'next_label' => $this->t('Pay and complete purchase'),
        'has_order_summary' => FALSE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibleSteps() {
    // All steps are visible by default.
    return $this->getSteps();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOrderSummary(array $form, FormStateInterface $form_state) {
    $order_summary = [];
    if (!empty($this->configuration['order_summary_view'])) {
      $order_summary = [
        '#type' => 'view',
        '#name' => $this->configuration['order_summary_view'],
        '#display_id' => 'default',
        '#arguments' => [$this->order->id()],
        '#embed' => TRUE,
      ];
    }

    return $order_summary;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'display_checkout_progress' => TRUE,
      'order_summary_view' => 'commerce_checkout_order_summary',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $view_storage = $this->entityTypeManager->getStorage('view');
    $available_summary_views = [];
    /** @var \Drupal\views\Entity\View $view */
    foreach ($view_storage->loadMultiple() as $view) {
      if (strpos($view->get('tag'), 'commerce_order_summary') !== FALSE) {
        $available_summary_views[$view->id()] = $view->label();
      }
    }

    $form['display_checkout_progress'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display checkout progress'),
      '#description' => $this->t('Used by the checkout progress block to determine visibility.'),
      '#default_value' => $this->configuration['display_checkout_progress'],
    ];
    $form['order_summary_view'] = [
      '#type' => 'select',
      '#title' => $this->t('Order summary view'),
      '#options' => $available_summary_views,
      '#empty_value' => '',
      '#default_value' => $this->configuration['order_summary_view'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['display_checkout_progress'] = $values['display_checkout_progress'];
      $this->configuration['order_summary_view'] = $values['order_summary_view'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $steps = $this->getVisibleSteps();
    $form['#tree'] = TRUE;
    $form['#title'] = $steps[$this->stepId]['label'];
    $form['#theme'] = ['commerce_checkout_form'];
    $form['#attached']['library'][] = 'commerce_checkout/form';
    if ($steps[$this->stepId]['has_order_summary']) {
      if ($order_summary = $this->buildOrderSummary($form, $form_state)) {
        $form['order_summary'] = $order_summary;
      }
    }
    $form['actions'] = $this->actions($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($next_step_id = $this->getNextStepId()) {
      $this->order->checkout_step = $next_step_id;
      $form_state->setRedirect('commerce_checkout.form', [
        'commerce_order' => $this->order->id(),
        'step' => $next_step_id,
      ]);

      if ($next_step_id == 'complete') {
        // Place the order.
        $transition = $this->order->getState()->getWorkflow()->getTransition('place');
        $this->order->getState()->applyTransition($transition);
      }
    }

    $this->order->save();
  }

  /**
   * {@inheritdoc}
   */
  public function previousForm(array &$form, FormStateInterface $form_state) {
    $previous_step_id = $this->getPreviousStepId();
    $this->order->checkout_step = $previous_step_id;
    $this->order->save();

    $form_state->setRedirect('commerce_checkout.form', [
      'commerce_order' => $this->order->id(),
      'step' => $previous_step_id,
    ]);
  }

  /**
   * Builds the actions element for the current form.
   *
   * @param array $form
   *   The current form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The actions element.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = [
      '#type' => 'actions',
    ];
    $steps = $this->getVisibleSteps();
    $previous_step_id = $this->getPreviousStepId();
    if ($previous_step_id && isset($steps[$previous_step_id]['previous_label'])) {
      $actions['previous'] = [
        '#type' => 'submit',
        '#value' => $steps[$previous_step_id]['previous_label'],
        '#submit' => [
          '::previousForm',
        ],
      ];
    }
    $next_step_id = $this->getNextStepId();
    if ($next_step_id && isset($steps[$next_step_id]['next_label'])) {
      $actions['next'] = [
        '#type' => 'submit',
        '#value' => $steps[$next_step_id]['next_label'],
        '#button_type' => 'primary',
        '#submit' => ['::submitForm'],
      ];
    }
    // Hide the actions element if it has no buttons.
    $actions['#access'] = isset($actions['previous']) || isset($actions['next']);
    // Once these two steps are reached, the user can't go back.
    if (in_array($this->stepId, ['offsite_payment', 'complete'])) {
      $actions['#access'] = FALSE;
    }

    return $actions;
  }

}
