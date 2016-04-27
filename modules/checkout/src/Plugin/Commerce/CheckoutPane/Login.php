<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\commerce\CredentialsCheckFloodInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\UserAuthInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * @param \Drupal\commerce\CredentialsCheckFloodInterface $credentials_check_flood
   *   The credentials check flood controller.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, CredentialsCheckFloodInterface $credentials_check_flood, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, UserAuthInterface $user_auth, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow);

    $this->credentialsCheckFlood = $credentials_check_flood;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('commerce.credentials_check_flood'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('user.auth'),
      $container->get('request_stack')
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
    $pane_form['returning_customer'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Returning Customer'),
    ];
    $pane_form['returning_customer']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#size' => 60,
      '#maxlength' => USERNAME_MAX_LENGTH,
      '#attributes' => [
        'autocorrect' => 'none',
        'autocapitalize' => 'none',
        'spellcheck' => 'false',
        'autofocus' => 'autofocus',
      ],
    ];
    $pane_form['returning_customer']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#size' => 60,
    ];
    // @todo Add a "forgotten password" link.
    $pane_form['returning_customer']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Log in'),
      '#op' => 'login',
    ];

    $pane_form['guest'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Guest Checkout'),
    ];
    $pane_form['guest']['text'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => $this->t('Proceed to checkout. You can optionally create an account at the end.'),
    ];
    $pane_form['guest']['continue'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue as Guest'),
      '#op' => 'continue',
    ];

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state) {
    parent::validatePaneForm($pane_form, $form_state);

    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#op'] == 'continue') {
      // No login in progress, nothing to validate.
      return;
    }

    $name_element = $pane_form['returning_customer']['name'];
    $values = $form_state->getValue($pane_form['#parents']);
    $username = $values['returning_customer']['name'];
    $password = trim($values['returning_customer']['password']);
    if (empty($username) || empty($password)) {
      $form_state->setErrorByName('name', $this->t('Unrecognized username or password.'));
      return;
    }
    if (user_is_blocked($username)) {
      $form_state->setError($name_element, $this->t('The username %name has not been activated or is blocked.', ['%name' => $username]));
      return;
    }
    if (!$this->credentialsCheckFlood->isAllowedHost($this->clientIp)) {
      $form_state->setErrorByName($name_element, $this->t('Too many failed login attempts from your IP address. This IP address is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', [':url' => Url::fromRoute('user.pass')]));
      $this->credentialsCheckFlood->register($this->clientIp, $username);
      return;
    }
    elseif (!$this->credentialsCheckFlood->isAllowedAccount($this->clientIp, $username)) {
      $form_state->setErrorByName($name_element, $this->t('Too many failed login attempts for this account. It is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', [':url' => Url::fromRoute('user.pass')]));
      $this->credentialsCheckFlood->register($this->clientIp, $username);
      return;
    }

    $uid = $this->userAuth->authenticate($username, $password);
    if (!$uid) {
      $this->credentialsCheckFlood->register($this->clientIp, $username);
      $form_state->setErrorByName('name', $this->t('Unrecognized username or password.'));
    }
    $form_state->set('logged_in_uid', $uid);
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#op'] == 'login') {
      $storage = $this->entityTypeManager->getStorage('user');
      $account = $storage->load($form_state->get('logged_in_uid'));
      user_login_finalize($account);
      $this->order->setOwner($account);
      $this->credentialsCheckFlood->clearAccount($this->clientIp, $account->getAccountName());
    }

    $form_state->setRedirect('commerce_checkout.form', [
      'commerce_order' => $this->order->id(),
      'step' => $this->checkoutFlow->getNextStepId(),
    ]);
  }

}
