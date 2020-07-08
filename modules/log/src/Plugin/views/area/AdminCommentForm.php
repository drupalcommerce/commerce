<?php

namespace Drupal\commerce_log\Plugin\views\area;

use Drupal\commerce_log\Form\LogCommentForm;
use Drupal\commerce_log\LogTemplateManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a log comment form.
 *
 * Displays a form that allows admins with the proper permission to add a
 * log as comment.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("commerce_log_admin_comment_form")
 */
class AdminCommentForm extends AreaPluginBase {

  use StringTranslationTrait;

  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The log template manager.
   *
   * @var \Drupal\commerce_log\LogTemplateManagerInterface
   */
  protected $logTemplateManager;

  /**
   * Constructs a new LogCommentForm object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\commerce_log\LogTemplateManagerInterface $log_template_manager
   *   The log template manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $account, FormBuilderInterface $form_builder, LogTemplateManagerInterface $log_template_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $account;
    $this->formBuilder = $form_builder;
    $this->logTemplateManager = $log_template_manager;
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
      $container->get('current_user'),
      $container->get('form_builder'),
      $container->get('plugin.manager.commerce_log_template')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if ($empty && empty($this->options['empty'])) {
      return [];
    }

    $source_entity_id = NULL;
    $source_entity_type = NULL;
    foreach ($this->view->argument as $argument) {
      if ($argument->getField() === 'commerce_log.source_entity_id') {
        $source_entity_id = $argument->getValue();
      }
      elseif ($argument->getField() === 'commerce_log.source_entity_type') {
        $source_entity_type = $argument->getValue();
      }
    }
    if ($source_entity_id === NULL || $source_entity_type === NULL) {
      return [];
    }
    $log_template_id = $source_entity_type . '_admin_comment';
    if (!$this->logTemplateManager->hasDefinition($log_template_id)) {
      return [];
    }

    $permission = $this->currentUser->hasPermission("add commerce_log {$source_entity_type} admin comment");
    if (!$permission) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage($source_entity_type);
    $entity = $storage->load($source_entity_id);
    if ($entity) {
      $form = $this->formBuilder->getForm(LogCommentForm::class, $source_entity_type, $source_entity_id, $log_template_id);
      $form['log_comment']['comment']['#description'] = $this->t('Your comment will only be visible to users who have access to the activity log.');
      return $form;
    }
    return [];
  }

}
