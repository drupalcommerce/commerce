<?php

namespace Drupal\commerce_checkout\Form;

use Drupal\commerce_checkout\CheckoutFlowManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CheckoutFlowForm extends EntityForm {

  /**
   * The checkout flow plugin manager.
   *
   * @var \Drupal\commerce_checkout\CheckoutFlowManager
   */
  protected $pluginManager;

  /**
   * Constructs a new CheckoutFlowForm object.
   *
   * @param \Drupal\commerce_checkout\CheckoutFlowManager $plugin_manager
   *   The checkout flow plugin manager.
   */
  public function __construct(CheckoutFlowManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_checkout_flow')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
    $checkout_flow = $this->entity;
    $plugins = array_column($this->pluginManager->getDefinitions(), 'label', 'id');
    asort($plugins);
    // Use the first available plugin as the default value.
    if (!$checkout_flow->getPluginId()) {
      $plugin_ids = array_keys($plugins);
      $plugin = reset($plugin_ids);
      $checkout_flow->setPluginId($plugin);
    }

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'commerce_checkout/admin';
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $checkout_flow->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $checkout_flow->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_checkout\Entity\CheckoutFlow::load',
      ],
      '#disabled' => !$checkout_flow->isNew(),
    ];
    $form['plugin'] = [
      '#type' => 'radios',
      '#title' => $this->t('Plugin'),
      '#options' => $plugins,
      '#default_value' => $checkout_flow->getPluginId(),
      '#required' => TRUE,
      '#disabled' => !$checkout_flow->isNew(),
    ];
    if (!$checkout_flow->isNew()) {
      $form['configuration'] = [
        '#parents' => ['configuration'],
      ];
      $form['configuration'] = $checkout_flow->getPlugin()->buildConfigurationForm($form['configuration'], $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
    $checkout_flow = $this->entity;
    if (!$checkout_flow->isNew()) {
      $checkout_flow->getPlugin()->validateConfigurationForm($form['configuration'], $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
    $checkout_flow = $this->entity;
    if (!$checkout_flow->isNew()) {
      $checkout_flow->getPlugin()->submitConfigurationForm($form['configuration'], $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();
    drupal_set_message($this->t('Saved the %label checkout flow.', ['%label' => $this->entity->label()]));
    if ($status == SAVED_UPDATED) {
      $form_state->setRedirect('entity.commerce_checkout_flow.collection');
    }
    elseif ($status == SAVED_NEW) {
      // Send the user to the Edit form to see the plugin configuration form.
      $form_state->setRedirect('entity.commerce_checkout_flow.edit_form', [
        'commerce_checkout_flow' => $this->entity->id(),
      ]);
    }
  }

}
