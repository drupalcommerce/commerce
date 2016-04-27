<?php

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the confirmation form for resetting attribute value ordering.
 */
class ProductAttributeResetForm extends EntityConfirmFormBase {

  /**
   * The attribute storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageBase
   */
  protected $valueStorage;

  /**
   * Constructs a new ProductAttributeResetForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The attribute storage.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->valueStorage = $entity_type_manager->getStorage('commerce_product_attribute_value');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_product_attribute_confirm_reset_alphabetical';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('overview-form');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to reset the @attribute attribute values to alphabetical order?', ['@attribute' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Get the new weights from an ordered query.
    $query = $this->valueStorage->getQuery();
    $query
      ->condition('attribute', $this->entity->id())
      ->sort('name');
    $value_ids = $query->execute();
    /** @var \Drupal\commerce_product\Entity\ProductAttributeValueInterface[] $values */
    $values = $this->valueStorage->loadMultiple($value_ids);
    $new_weight = 0;
    foreach ($values as $value) {
      $value->setWeight($new_weight);
      $value->save();
      $new_weight++;
    }

    drupal_set_message($this->t('The @attribute attribute values have been reset to alphabetical order.', ['@attribute' => $this->entity->label()]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
