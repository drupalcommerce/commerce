<?php

namespace Drupal\commerce_payment\Form;

use Drupal\commerce\Form\CommercePluginEntityFormBase;
use Drupal\commerce_payment\PaymentGatewayManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentGatewayForm extends CommercePluginEntityFormBase {

  /**
   * The payment gateway plugin manager.
   *
   * @var \Drupal\commerce_payment\PaymentGatewayManager
   */
  protected $pluginManager;

  /**
   * Constructs a new PaymentGatewayForm object.
   *
   * @param \Drupal\commerce_payment\PaymentGatewayManager $plugin_manager
   *   The payment gateway plugin manager.
   */
  public function __construct(PaymentGatewayManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_payment_gateway')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $gateway */
    $gateway = $this->entity;
    $plugins = array_map(function ($definition) {
      return $definition['label'];
    }, $this->pluginManager->getDefinitions());

    $form['#tree'] = TRUE;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $gateway->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $gateway->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_payment\Entity\PaymentGateway::load',
      ],
    ];
    $form['plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Plugin'),
      '#options' => $plugins,
      '#default_value' => $gateway->getPluginId(),
      '#required' => TRUE,
      '#disabled' => !$gateway->isNew(),
    ];
    if (!$gateway->isNew()) {
      $form['configuration'] = [
        '#parents' => ['configuration'],
      ];
      $form['configuration'] = $gateway->getPlugin()->buildConfigurationForm($form['configuration'], $form_state);
    }
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $gateway->status(),
    ];

    return $this->protectPluginIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $entity */
    // The parent method tries to initialize the plugin collection before
    // setting the plugin.
    $entity->setPluginId($form_state->getValue('plugin'));

    parent::copyFormValuesToEntity($entity, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $gateway */
    $gateway = $this->entity;
    if (!$gateway->isNew()) {
      $gateway->getPlugin()->validateConfigurationForm($form['configuration'], $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $gateway */
    $gateway = $this->entity;
    if (!$gateway->isNew()) {
      $gateway->getPlugin()->submitConfigurationForm($form['configuration'], $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();
    drupal_set_message($this->t('Saved the %label payment gateway.', ['%label' => $this->entity->label()]));
    if ($status == SAVED_UPDATED) {
      $form_state->setRedirect('entity.commerce_payment_gateway.collection');
    }
    elseif ($status == SAVED_NEW) {
      // Send the user to the Edit form to see the plugin configuration form.
      $form_state->setRedirect('entity.commerce_payment_gateway.edit_form', [
        'commerce_payment_gateway' => $this->entity->id(),
      ]);
    }
  }

}
