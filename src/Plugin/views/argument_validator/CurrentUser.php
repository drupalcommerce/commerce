<?php

/**
 * @file
 * Contains \Drupal\commerce\Plugin\views\argument_validator\CurrentUser.
 */

namespace Drupal\commerce\Plugin\views\argument_validator;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validates whether the argument matches the current user or a role.
 *
 * @ViewsArgumentValidator(
 *   id = "commerce_current_user",
 *   title = @Translation("Current user or role"),
 *   entity_type = "user"
 * )
 */
class CurrentUser extends ArgumentValidatorPluginBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CurrentUser object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['restrict_roles'] = ['default' => FALSE];
    $options['roles'] = ['default' => []];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['restrict_roles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('If the provided user does not match the current user, restrict by role.'),
      '#default_value' => $this->options['restrict_roles'],
    ];
    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Grant access for the selected roles'),
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names(TRUE)),
      '#default_value' => $this->options['roles'],
      '#states' => [
        'visible' => [
          ':input[name="options[validate][options][' . $this->definition['id'] . '][restrict_roles]"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state, &$options = []) {
    // Don't store unselected roles.
    $options['roles'] = array_filter($options['roles']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateArgument($argument) {
    if (!is_numeric($argument)) {
      return FALSE;
    }
    $user_storage = $this->entityTypeManager->getStorage('user');
    /** @var \Drupal\user\UserInterface $user */
    $user = $user_storage->load($argument);
    if (empty($user)) {
      return FALSE;
    }

    if ($user->id() == $this->currentUser->id()) {
      return TRUE;
    }
    elseif (!empty($this->options['restrict_roles']) && !empty($this->options['roles'])) {
      return !empty(array_intersect($user->getRoles(), $this->options['roles']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $role_storage = $this->entityTypeManager->getStorage('user_role');
    $role_names = array_keys($this->options['roles']);
    foreach ($role_storage->loadMultiple($role_names) as $role) {
      $dependencies[$role->getConfigDependencyKey()][] = $role->getConfigDependencyName();
    }

    return $dependencies;
  }

}
