<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow;

use Drupal\commerce_checkout\CheckoutPaneManager;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a base checkout flow that uses checkout panes.
 */
abstract class CheckoutFlowWithPanesBase extends CheckoutFlowBase implements CheckoutFlowWithPanesInterface {

  /**
   * The checkout pane manager.
   *
   * @var \Drupal\commerce_checkout\CheckoutPaneManager
   */
  protected $paneManager;

  /**
   * The initialized pane plugins.
   *
   * @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface[]
   */
  protected $panes = [];

  /**
   * Static cache of visible steps.
   *
   * @var array
   */
  protected $visibleSteps = [];

  /**
   * Constructs a new CheckoutFlowWithPanesBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pane_id
   *   The plugin_id for the plugin instance.
   * @param mixed $pane_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\commerce_checkout\CheckoutPaneManager $pane_manager
   *   The checkout pane manager.
   */
  public function __construct(array $configuration, $pane_id, $pane_definition, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, RouteMatchInterface $route_match, CheckoutPaneManager $pane_manager) {
    $this->paneManager = $pane_manager;

    parent::__construct($configuration, $pane_id, $pane_definition, $entity_type_manager, $event_dispatcher, $route_match);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pane_id, $pane_definition) {
    return new static(
      $configuration,
      $pane_id,
      $pane_definition,
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('current_route_match'),
      $container->get('plugin.manager.commerce_checkout_pane')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPanes() {
    if (empty($this->panes)) {
      foreach ($this->paneManager->getDefinitions() as $pane_id => $pane_definition) {
        $pane_configuration = $this->getPaneConfiguration($pane_id);
        $pane = $this->paneManager->createInstance($pane_id, $pane_configuration, $this);
        $this->panes[$pane_id] = [
          'pane' => $pane,
          'weight' => $pane->getWeight(),
        ];
      }
      // Sort the panes and flatten the array.
      uasort($this->panes, [SortArray::class, 'sortByWeightElement']);
      $this->panes = array_map(function ($pane_data) {
        return $pane_data['pane'];
      }, $this->panes);
    }

    return $this->panes;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisiblePanes($step_id) {
    $panes = $this->getPanes();
    $panes = array_filter($panes, function ($pane) use ($step_id) {
      /** @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface $pane */
      return ($pane->getStepId() == $step_id) && $pane->isVisible();
    });

    return $panes;
  }

  /**
   * {@inheritdoc}
   */
  public function getPane($pane_id) {
    $panes = $this->getPanes();
    return isset($panes[$pane_id]) ? $panes[$pane_id] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibleSteps() {
    if (empty($this->visibleSteps)) {
      $steps = $this->getSteps();
      foreach ($steps as $step_id => $step) {
        // A step is visible if it has at least one visible pane.
        if (empty($this->getVisiblePanes($step_id))) {
          unset($steps[$step_id]);
        }
      }
      $this->visibleSteps = $steps;
    }

    return $this->visibleSteps;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    // Merge-in the pane dependencies.
    foreach ($this->getPanes() as $pane) {
      foreach ($pane->calculateDependencies() as $dependency_type => $list) {
        foreach ($list as $name) {
          $dependencies[$dependency_type][] = $name;
        }
      }
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'panes' => [],
    ];
  }

  /**
   * Gets the configuration for the given pane.
   *
   * @param string $pane_id
   *   The pane ID.
   *
   * @return array
   *   The pane configuration.
   */
  protected function getPaneConfiguration($pane_id) {
    $pane_configuration = [];
    if (isset($this->configuration['panes'][$pane_id])) {
      $pane_configuration = $this->configuration['panes'][$pane_id];
    }

    return $pane_configuration;
  }

  /**
   * Get the regions for the checkout pane overview table.
   *
   * @return array
   *   The table regions, keyed by step ID.
   */
  protected function getTableRegions() {
    $regions = [];
    foreach ($this->getSteps() as $step_id => $step) {
      $regions[$step_id] = [
        'title' => $step['label'],
        'message' => $this->t('No pane is displayed.'),
      ];
    }
    $regions['_sidebar'] = [
      'title' => $this->t('Sidebar'),
      'message' => $this->t('No pane is displayed.'),
    ];
    $regions['_disabled'] = [
      'title' => $this->t('Disabled', [], ['context' => 'Plural']),
      'message' => $this->t('No pane is disabled.'),
    ];

    return $regions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    if (!$form_state->has('panes')) {
      $form_state->set('panes', $this->getPanes());
    }
    // Group the panes by step id for region display.
    $grouped_panes = [];
    foreach ($form_state->get('panes') as $pane_id => $pane) {
      /** @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface $pane */
      $step_id = $pane->getStepId();
      $grouped_panes[$step_id][$pane_id] = $pane;
    }

    $wrapper_id = Html::getUniqueId('checkout-pane-overview-wrapper');
    $form['panes'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Pane'),
        $this->t('Weight'),
        $this->t('Step'),
        ['data' => $this->t('Settings'), 'colspan' => 2],
      ],
      '#attributes' => [
        'class' => ['checkout-pane-overview'],
        // Used by the JS code when attaching behaviors.
        'id' => 'checkout-pane-overview',
      ],
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      '#wrapper_id' => $wrapper_id,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'pane-weight',
        ],
        [
          'action' => 'match',
          'relationship' => 'self',
          'group' => 'pane-step',
          'subgroup' => 'pane-step',
          'source' => 'pane-id',
        ],
      ],
    ];
    foreach ($this->getTableRegions() as $step_id => $region) {
      $form['panes']['region-' . $step_id] = [
        '#attributes' => [
          'class' => ['region-title'],
          'no_striping' => TRUE,
        ],
      ];
      $form['panes']['region-' . $step_id]['title'] = [
        '#markup' => $region['title'],
        '#wrapper_attributes' => ['colspan' => 5],
      ];
      $form['panes']['region-' . $step_id . '-message'] = [
        '#attributes' => [
          'class' => [
            'region-message',
            'region-' . $step_id . '-message',
            empty($grouped_panes[$step_id]) ? 'region-empty' : 'region-populated',
          ],
          'no_striping' => TRUE,
        ],
      ];
      $form['panes']['region-' . $step_id . '-message']['message'] = [
        '#markup' => $region['message'],
        '#wrapper_attributes' => ['colspan' => 5],
      ];
      if (!empty($grouped_panes[$step_id])) {
        foreach ($grouped_panes[$step_id] as $pane_id => $pane) {
          $form['panes'][$pane_id] = $this->buildPaneRow($pane, $form, $form_state);
        }
      }
    }

    return $form;
  }

  /**
   * Builds the table row structure for a checkout pane.
   *
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface $pane
   *   The checkout pane.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   A table row array.
   */
  protected function buildPaneRow(CheckoutPaneInterface $pane, array &$form, FormStateInterface $form_state) {
    $pane_id = $pane->getPluginId();
    $label = $pane->getLabel();
    $region_titles = array_map(function ($region) {
      return $region['title'];
    }, $this->getTableRegions());

    $pane_row = [
      '#attributes' => [
        'class' => ['draggable', 'tabledrag-leaf'],
      ],
      'human_name' => [
        '#plain_text' => $label,
      ],
      'weight' => [
        '#type' => 'textfield',
        '#title' => $this->t('Weight for @title', ['@title' => $label]),
        '#title_display' => 'invisible',
        '#default_value' => $pane->getWeight(),
        '#size' => 3,
        '#attributes' => [
          'class' => ['pane-weight'],
        ],
      ],
      'step_wrapper' => [
        '#parents' => array_merge($form['#parents'], ['panes', $pane_id]),
        'step_id' => [
          '#type' => 'select',
          '#title' => $this->t('Checkout step for @title', ['@title' => $label]),
          '#title_display' => 'invisible',
          '#options' => $region_titles,
          '#default_value' => $pane->getStepId(),
          '#attributes' => ['class' => ['js-pane-step', 'pane-step']],
        ],
        'pane_id' => [
          '#type' => 'hidden',
          '#default_value' => $pane_id,
          '#attributes' => ['class' => ['pane-id']],
        ],
      ],
    ];

    $base_button = [
      '#submit' => [
        [get_class($this), 'multistepSubmit'],
      ],
      '#ajax' => [
        'callback' => [get_class($this), 'multistepAjax'],
        'wrapper' => $form['panes']['#wrapper_id'],
      ],
      '#pane_id' => $pane_id,
    ];

    if ($form_state->get('pane_configuration_edit') == $pane_id) {
      $pane_row['#attributes']['class'][] = 'pane-configuration-editing';

      $pane_row['configuration'] = [
        '#parents' => array_merge($form['#parents'], ['panes', $pane_id, 'configuration']),
        '#type' => 'container',
        '#wrapper_attributes' => ['colspan' => 2],
        '#attributes' => [
          'class' => ['pane-configuration-edit-form'],
        ],
        '#element_validate' => [
          [get_class($this), 'validatePaneConfigurationForm'],
        ],
        '#pane_id' => $pane_id,
      ];
      $pane_row['configuration'] = $pane->buildConfigurationForm($pane_row['configuration'], $form_state);
      $pane_row['configuration']['actions'] = [
        '#type' => 'actions',
        'save' => $base_button + [
          '#type' => 'submit',
          '#button_type' => 'primary',
          '#name' => $pane_id . '_pane_configuration_update',
          '#value' => $this->t('Update'),
          '#op' => 'update',
        ],
        'cancel' => $base_button + [
          '#type' => 'submit',
          '#name' => $pane_id . '_plugin_settings_cancel',
          '#value' => $this->t('Cancel'),
          '#op' => 'cancel',
          '#limit_validation_errors' => [],
        ],
      ];
    }
    else {
      $pane_row['configuration_summary'] = [];
      $pane_row['configuration_edit'] = [];

      $summary = $pane->buildConfigurationSummary();
      if (!empty($summary)) {
        $pane_row['configuration_summary'] = [
          '#markup' => $summary,
          '#prefix' => '<div class="pane-configuration-summary">',
          '#suffix' => '</div>',
          '#wrapper_attributes' => [
            'class' => ['pane-configuration-summary-cell'],
          ],
        ];
      }
      // Check selected plugin settings to display edit link or not.
      $settings_form = $pane->buildConfigurationForm([], $form_state);
      if (!empty($settings_form)) {
        $pane_row['configuration_edit'] = $base_button + [
          '#type' => 'image_button',
          '#name' => $pane_id . '_configuration_edit',
          '#src' => 'core/misc/icons/787878/cog.svg',
          '#attributes' => ['class' => ['pane-configuration-edit'], 'alt' => $this->t('Edit')],
          '#op' => 'edit',
          '#limit_validation_errors' => [],
          '#prefix' => '<div class="pane-configuration-edit-wrapper">',
          '#suffix' => '</div>',
        ];
      }
    }

    return $pane_row;
  }

  /**
   * #element_validate callback: Validates for the pane configuration form.
   *
   * @param array $pane_configuration_form
   *   The pane configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The complete form state.
   */
  public static function validatePaneConfigurationForm(array &$pane_configuration_form, FormStateInterface $form_state) {
    $pane_id = $pane_configuration_form['#pane_id'];
    /** @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface[] $panes */
    $panes = $form_state->get('panes');
    $pane = &$panes[$pane_id];
    $pane->validateConfigurationForm($pane_configuration_form, $form_state);
    $form_state->set('panes', $panes);
  }

  /**
   * Form submission handler for multistep buttons.
   *
   * @param array $form
   *   The parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The complete form state.
   */
  public static function multistepSubmit(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    switch ($triggering_element['#op']) {
      case 'edit':
        // Open the configuration form.
        $form_state->set('pane_configuration_edit', $triggering_element['#pane_id']);
        break;

      case 'update':
        $form_state->set('pane_configuration_edit', NULL);
        // Submit the pane configuration form and update the pane in form state.
        $pane_id = $triggering_element['#pane_id'];
        $parents = array_slice($triggering_element['#parents'], 0, -2);
        $pane_configuration_form = NestedArray::getValue($form, $parents);
        /** @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface[] $panes */
        $panes = $form_state->get('panes');
        $pane = &$panes[$pane_id];
        $pane->submitConfigurationForm($pane_configuration_form, $form_state);
        $form_state->set('panes', $panes);
        break;

      case 'cancel':
        // Close the configuration form.
        $form_state->set('pane_configuration_edit', NULL);
        break;
    }

    $form_state->setRebuild();
  }

  /**
   * Ajax handler for multistep buttons.
   */
  public static function multistepAjax($form, FormStateInterface $form_state) {
    // $form is the parent config entity form, not the plugin form.
    return $form['configuration']['panes'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $panes = $form_state->get('panes');
    // If the main "Save" button was submitted while a pane configuration
    // subform was being edited, update the configuration as if the subform's
    // "Update" button had been submitted.
    if ($pane_id = $form_state->get('pane_configuration_edit')) {
      $parents = ['panes', $pane_id, 'configuration'];
      $pane_configuration_form = NestedArray::getValue($form, $parents);
      /** @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface[] $panes */
      $pane = &$panes[$pane_id];
      $pane->submitConfigurationForm($pane_configuration_form, $form_state);
    }

    $form_values = $form_state->getValue($form['#parents']);
    foreach ($form_values['panes'] as $pane_id => $pane_values) {
      $pane = $panes[$pane_id];
      // If the pane was disabled, reset its configuration.
      if ($pane_values['step_id'] == '_disabled') {
        $pane->setConfiguration([]);
      }
      // Transfer the step and weight changes from the form.
      $pane->setStepId($pane_values['step_id']);
      $pane->setWeight($pane_values['weight']);
    }

    // Store the pane configuration.
    $this->configuration['panes'] = [];
    foreach ($panes as $pane_id => $pane) {
      $this->configuration['panes'][$pane_id] = $pane->getConfiguration();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $step_id = NULL) {
    $form = parent::buildForm($form, $form_state, $step_id);
    if ($form_state->isRebuilding()) {
      // The order reference on the panes might be outdated due to
      // the form cache, so update the order manually once the
      // parent class reloads it.
      foreach ($this->panes as $pane_id => $pane) {
        $this->panes[$pane_id] = $pane->setOrder($this->order);
      }
    }

    foreach ($this->getVisiblePanes($step_id) as $pane_id => $pane) {
      $form[$pane_id] = [
        '#parents' => [$pane_id],
        '#type' => $pane->getWrapperElement(),
        '#title' => $pane->getDisplayLabel(),
        '#attributes' => [
          'class' => ['checkout-pane', 'checkout-pane-' . str_replace('_', '-', $pane_id)],
        ],
      ];
      $form[$pane_id] = $pane->buildPaneForm($form[$pane_id], $form_state, $form);
    }
    if ($this->hasSidebar($step_id)) {
      // The base class adds a hardcoded order summary view to the sidebar.
      // Remove it, there's a pane for that.
      unset($form['sidebar']);

      foreach ($this->getVisiblePanes('_sidebar') as $pane_id => $pane) {
        $form['sidebar'][$pane_id] = [
          '#parents' => ['sidebar', $pane_id],
          '#type' => $pane->getWrapperElement(),
          '#title' => $pane->getDisplayLabel(),
          '#attributes' => [
            'class' => ['checkout-pane', 'checkout-pane-' . str_replace('_', '-', $pane_id)],
          ],
        ];
        $form['sidebar'][$pane_id] = $pane->buildPaneForm($form['sidebar'][$pane_id], $form_state, $form);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    foreach ($this->getVisiblePanes($form['#step_id']) as $pane_id => $pane) {
      $pane->validatePaneForm($form[$pane_id], $form_state, $form);
    }
    if ($this->hasSidebar($form['#step_id'])) {
      foreach ($this->getVisiblePanes('_sidebar') as $pane_id => $pane) {
        $pane->validatePaneForm($form['sidebar'][$pane_id], $form_state, $form);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->getVisiblePanes($form['#step_id']) as $pane_id => $pane) {
      $pane->submitPaneForm($form[$pane_id], $form_state, $form);
    }
    if ($this->hasSidebar($form['#step_id'])) {
      foreach ($this->getVisiblePanes('_sidebar') as $pane_id => $pane) {
        $pane->submitPaneForm($form['sidebar'][$pane_id], $form_state, $form);
      }
    }

    parent::submitForm($form, $form_state);
  }

}
