<?php

namespace Drupal\commerce_log\Form;

use Drupal\commerce_log\LogStorageInterface;
use Drupal\commerce_log\LogTemplateManagerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LogCommentForm extends FormBase {

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
   * Constructs a new LogCommentForm object.
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'log_comment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $source_entity_type = NULL, $source_entity_id = NULL, $log_template_id = NULL) {
    if ($source_entity_type === NULL || $source_entity_id === NULL || $log_template_id === NULL) {
      return [];
    }
    if (!$this->logTemplateManager->hasDefinition($log_template_id)) {
      return [];
    }
    $entity_type = $this->entityTypeManager->getDefinition($source_entity_type);
    assert($entity_type !== NULL);

    $form['log_comment'] = [
      '#type' => 'details',
      '#title' => $this->t('Comment on this @label', ['@label' => $entity_type->getSingularLabel()]),
      '#open' => FALSE,
    ];
    $form['log_comment']['source_entity_id'] = [
      '#type' => 'hidden',
      '#value' => $source_entity_id,
    ];
    $form['log_comment']['source_entity_type'] = [
      '#type' => 'hidden',
      '#value' => $source_entity_type,
    ];
    $form['log_comment']['log_template_id'] = [
      '#type' => 'hidden',
      '#value' => $log_template_id,
    ];
    $form['log_comment']['comment'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Comment'),
      '#title_display' => 'invisible',
      '#required' => TRUE,
    ];
    $form['log_comment']['actions']['#type'] = 'actions';
    $form['log_comment']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add comment'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $log_storage = $this->entityTypeManager->getStorage('commerce_log');
    assert($log_storage instanceof LogStorageInterface);
    $storage = $this->entityTypeManager->getStorage($form_state->getValue('source_entity_type'));
    assert($storage instanceof EntityStorageInterface);
    $entity = $storage->load($form_state->getValue('source_entity_id'));
    assert($entity instanceof ContentEntityInterface);
    $comment = nl2br(Html::escape($form_state->getValue('comment')));
    $log_storage->generate($entity, $form_state->getValue('log_template_id'), ['comment' => $comment])->save();
    $this->messenger()->addStatus($this->t('Comment saved'));
  }

}
