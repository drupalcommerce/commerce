<?php

/**
 * @file
 * Contains \Drupal\commerce\Form\DeleteMultiple.
 */

namespace Drupal\commerce\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a commerce entities deletion confirmation form.
 */
class DeleteMultiple extends ConfirmFormBase {

  /**
   * The array of commerce entities to delete.
   *
   * @var array
   */
  protected $entityInfo = [];

  /**
   * The entity type id.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity_type object.
   *
   * @var \Drupal\Core\Entity\ContentEntityType
   */
  protected $entityType;

  /**
   * The private_tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * The store storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $manager;

  /**
   * The form id.
   *
   * @var string
   */
  protected $formId;

  /**
   * The cancel URL - commerce entity collection.
   *
   * @var string
   */
  protected $cancelUrl;

  /**
   * Constructs a Commerce DeleteMultiple form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $privateTempStoreFactory, EntityTypeManagerInterface $manager) {
    $this->privateTempStoreFactory = $privateTempStoreFactory;
    $this->entityTypeId = \Drupal::routeMatch()->getParameter('entity_type');
    $this->entityType = \Drupal::entityTypeManager()->getDefinition($this->entityTypeId);
    $this->storage = $manager->getStorage($this->entityTypeId);
    $this->formId = $this->entityTypeId . '_multiple_delete_confirm';
    $this->cancelUrl = 'entity.' . $this->entityTypeId . '.collection';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->formId;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return \Drupal::translation()->formatPlural(count($this->entityInfo), 'Are you sure you want to delete this @entity_type_label?', 'Are you sure you want to delete these @entity_type_label_plural?', ['@entity_type_label' => $this->entityType->get('label_singular'), '@entity_type_label_plural' => $this->entityType->get('label_plural')]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url($this->cancelUrl);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->entityInfo = $this->privateTempStoreFactory->get($this->formId)->get(\Drupal::currentUser()->id());
    if (empty($this->entityInfo)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    $entities = $this->storage->loadMultiple(array_keys($this->entityInfo));
    $items = [];
    foreach ($entities as $id => $entity) {
      $items[$id] = $entity->label();
    }

    $form['entities'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->entityInfo)) {
      $entities = $this->storage->loadMultiple(array_keys($this->entityInfo));
      $delete_entities = [];
      foreach ($entities as $id => $entity) {
        $delete_entities[$id] = $entity;
      }
      if ($delete_entities) {
        $this->storage->delete($delete_entities);
        $count = count($delete_entities);
        $this->logger('content')->notice('Deleted @count @entity_type_label_plural.', ['@count' => $count, '@entity_type_label_plural' => $this->entityType->get('label_plural')]);
        drupal_set_message($this->formatPlural($count, 'Deleted 1 @entity_type_label.', 'Deleted @count @entity_type_label_plural.', ['@count' => $count, '@entity_type_label' => $this->entityType->get('label_singular'), '@entity_type_label_plural' => $this->entityType->get('label_plural')]));
      }
      $this->privateTempStoreFactory->get($this->formId)->delete(\Drupal::currentUser()->id());
    }
    $form_state->setRedirect($this->cancelUrl);
  }

}
