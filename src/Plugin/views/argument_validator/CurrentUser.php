<?php

namespace Drupal\commerce\Plugin\views\argument_validator;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\UserInterface;
use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validates whether the argument matches the current user.
 *
 * Anonymous users will always be considered invalid.
 *
 * @ViewsArgumentValidator(
 *   id = "commerce_current_user",
 *   title = @Translation("Current user"),
 *   entity_type = "user"
 * )
 */
class CurrentUser extends ArgumentValidatorPluginBase implements CacheableDependencyInterface {

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
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, PermissionHandlerInterface $permission_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->permissionHandler = $permission_handler;
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
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('user.permissions')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['admin_permission'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    // Build the list of all permissions grouped by module.
    $permissions = [];
    foreach ($this->permissionHandler->getPermissions() as $permission => $permission_item) {
      $provider = $permission_item['provider'];
      $display_name = $this->moduleHandler->getName($provider);
      $permissions[$display_name][$permission] = Html::escape($permission_item['title']);
    }

    $form['admin_permission'] = [
      '#type' => 'select',
      '#title' => $this->t('Admin permission'),
      '#description' => $this->t('Allows the current user to access the view even if the argument is a different user.'),
      '#options' => $permissions,
      '#empty_value' => '',
      '#default_value' => $this->options['admin_permission'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateArgument($argument) {
    if (!is_numeric($argument)) {
      return FALSE;
    }
    $user_storage = $this->entityTypeManager->getStorage('user');
    $user = $user_storage->load($argument);
    if (!$user instanceof UserInterface) {
      return FALSE;
    }
    if ($user->isAnonymous()) {
      return FALSE;
    }
    if ($user->id() == $this->currentUser->id()) {
      return TRUE;
    }
    if (!empty($this->options['admin_permission'])) {
      return $this->currentUser->hasPermission($this->options['admin_permission']);
    }

    // Return false by default.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
