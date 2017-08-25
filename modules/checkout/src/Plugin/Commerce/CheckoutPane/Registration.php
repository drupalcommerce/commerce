<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\commerce\CredentialsCheckFloodInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserAuthInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the registration pane.
 *
 * @CommerceCheckoutPane(
 *   id = "registration",
 *   label = @Translation("Guest registration after checkout"),
 *   default_step = "complete",
 * )
 */
class Registration extends CheckoutPaneBase implements CheckoutPaneInterface, ContainerFactoryPluginInterface {

  /**
   * The credentials check flood controller.
   *
   * @var \Drupal\commerce\CredentialsCheckFloodInterface
   */
  protected $credentialsCheckFlood;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The user authentication object.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The client IP address.
   *
   * @var string
   */
  protected $clientIp;

  /**
   * Constructs a new Registration object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce\CredentialsCheckFloodInterface $credentials_check_flood
   *   The credentials check flood controller.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, CredentialsCheckFloodInterface $credentials_check_flood, AccountInterface $current_user, UserAuthInterface $user_auth, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);

    $this->credentialsCheckFlood = $credentials_check_flood;
    $this->currentUser = $current_user;
    $this->userAuth = $user_auth;
    $this->clientIp = $request_stack->getCurrentRequest()->getClientIp();
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
      $container->get('entity_type.manager'),
      $container->get('commerce.credentials_check_flood'),
      $container->get('current_user'),
      $container->get('user.auth'),
      $container->get('request_stack')
    );
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
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form = [
      '#type' => 'fieldset',
      '#title' => $this->t('Account information'),
    ];

    $pane_form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => USERNAME_MAX_LENGTH,
      '#description' => $this->t("Several special characters are allowed, including space, period (.), hyphen (-), apostrophe ('), underscore (_), and the @ sign."),
      '#required' => FALSE,
      '#attributes' => [
        'class' => ['username'],
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
      ],
      '#default_value' => '',
    ];

    $pane_form['password'] = [
      '#type' => 'password_confirm',
      '#size' => 60,
      '#description' => $this->t('Provide a password for the new account in both fields.'),
      '#required' => FALSE,
    ];

    $pane_form['register'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save details'),
    ];

    return [
      '#theme' => 'commerce_checkout_registration',
      'form' => $pane_form,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);

    $username = $values['form']['name'];
    $password = trim($values['form']['password']);

    if (empty($username)) {
      $form_state->setError($pane_form['form']['name'], $this->t('Username field is required.'));
      return;
    }
    if (empty($password)) {
      $form_state->setError($pane_form['form']['password'], $this->t('Password field is required.'));
      return;
    }

    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->create([
      'mail' => $this->order->getEmail(),
      'name' => $username,
      'pass' => $password,
      'status' => TRUE,
    ]);

    // Validate the entity. This will ensure that the username and email
    // are in the right format and not already taken.
    $violations = $account->validate();
    foreach ($violations->getByFields(['name']) as $violation) {
      list($field_name) = explode('.', $violation->getPropertyPath(), 2);
      $form_state->setError($pane_form['form'][$field_name], $violation->getMessage());
    }

    if (!$form_state->hasAnyErrors()) {
      $account->save();
      $form_state->set('logged_in_uid', $account->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $storage = $this->entityTypeManager->getStorage('user');
    /** @var \Drupal\user\UserInterface $account */
    $account = $storage->load($form_state->get('logged_in_uid'));
    user_login_finalize($account);
    $this->order->setCustomer($account);

    // Normally advancing steps in the checkout automatically saves the order.
    // Since this pane occurs on the last step, manual order saving is needed.
    $this->order->save();

    $this->credentialsCheckFlood->clearAccount($this->clientIp, $account->getAccountName());

    $form_state->setRedirect('commerce_checkout.form', [
      'commerce_order' => $this->order->id(),
      'step' => $complete_form['#step_id'],
    ]);
  }

}

