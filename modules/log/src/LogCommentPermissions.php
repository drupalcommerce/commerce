<?php

namespace Drupal\commerce_log;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LogCommentPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The log template manager.
   *
   * @var \Drupal\commerce_log\LogTemplateManagerInterface
   */
  protected $logTemplateManager;

  /**
   * Constructs a new CommentPermissions object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_log\LogTemplateManagerInterface $log_template_manager
   *   The log template manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LogTemplateManagerInterface $log_template_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logTemplateManager = $log_template_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_log_template')
    );
  }

  /**
   * Builds a list of permissions for entity types that support comments..
   *
   * @return array
   *   The permissions.
   */
  public function buildPermissions() {
    $permissions = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      $entity_type_id = $entity_type->id();
      $log_template_id = "{$entity_type_id}_admin_comment";
      if ($this->logTemplateManager->hasDefinition($log_template_id)) {
        $permissions["add commerce_log ${entity_type_id} admin comment"] = [
          'title' => $this->t('Add admin comments to @label', ['@label' => $entity_type->getSingularLabel()]),
          'description' => $this->t('Provides the ability to add admin comments to @label.', ['@label' => $entity_type->getPluralLabel()]),
          'restrict access' => TRUE,
          'provider' => $entity_type->getProvider(),
        ];
      }
    }
    return $permissions;
  }

}
