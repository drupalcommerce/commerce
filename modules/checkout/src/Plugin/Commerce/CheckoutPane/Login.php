<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\user\Form\UserLoginForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the login or continue pane.
 *
 * @CommerceCheckoutPane(
 *   id = "login",
 *   label = "Login or continue as guest",
 *   default_step = "login",
 * )
 */
class Login extends CheckoutPaneBase implements CheckoutPaneInterface, ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new Login object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'allow_guest_checkout' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    if (!empty($this->configuration['allow_guest_checkout'])) {
      $summary = $this->t('Guest checkout: Allowed');
    }
    else {
      $summary = $this->t('Guest checkout: Not allowed');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['allow_guest_checkout'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow guest checkout'),
      '#default_value' => $this->configuration['allow_guest_checkout'],
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
      $this->configuration['allow_guest_checkout'] = !empty($values['allow_guest_checkout']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    return $this->currentUser->isAnonymous();
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state) {
    // @todo
    return $pane_form;
  }

}
