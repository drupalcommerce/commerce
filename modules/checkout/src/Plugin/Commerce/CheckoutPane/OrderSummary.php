<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Order summary pane.
 *
 * @CommerceCheckoutPane(
 *   id = "order_summary",
 *   label = @Translation("Order summary"),
 *   default_step = "_sidebar",
 *   wrapper_element = "container",
 * )
 */
class OrderSummary extends CheckoutPaneBase implements CheckoutPaneInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // @todo remove this, if checkbox not yet, remove `view`
      'use_views' => FALSE,
      'view' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    if (!$this->configuration['view']) {
      return $this->t('Using template to display order summary');
    }
    else {
      $view_storage = $this->entityTypeManager->getStorage('view');
      $view = $view_storage->load($this->configuration['view']);
      if ($view) {
        return $this->t('View: @view', ['@view' => $view->label()]);;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['use_views'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use a View to display order summary'),
      '#description' => $this->t('This setting allows you to use a View to override the template output of the order summary'),
      '#default_value' => $this->configuration['view'],
    ];

    $view_storage = $this->entityTypeManager->getStorage('view');
    $available_summary_views = [];
    /** @var \Drupal\views\Entity\View $view */
    foreach ($view_storage->loadMultiple() as $view) {
      if (strpos($view->get('tag'), 'commerce_order_summary') !== FALSE) {
        $available_summary_views[$view->id()] = $view->label();
      }
    }

    $form['view'] = [
      '#type' => 'select',
      '#title' => $this->t('View'),
      '#options' => $available_summary_views,
      '#default_value' => $this->configuration['view'],
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="configuration[panes][order_summary][configuration][use_views]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      if (!$values['use_views']) {
        $this->configuration['view'] = NULL;
      }
      else {
        $this->configuration['view'] = $values['view'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    if (!$this->configuration['view']) {
      $pane_form['summary'] = [
        '#theme' => 'commerce_checkout_order_summary',
        '#order' => $this->order,
        '#checkout_flow_plugin_id' => $this->getPluginId(),
        '#checkout_flow_step' => $complete_form['#step_id'],
      ];
    }
    else {
      $pane_form['summary'] = [
        '#type' => 'view',
        '#name' => $this->configuration['view'],
        '#display_id' => 'default',
        '#arguments' => [$this->order->id()],
        '#embed' => TRUE,
      ];
    }

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {}

}
