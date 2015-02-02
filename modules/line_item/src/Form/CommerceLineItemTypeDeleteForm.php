<?php

/**
 * @file
 * Contains \Drupal\commerce_line_item\Form\CommerceLineItemTypeDeleteForm.
 */

namespace Drupal\commerce_line_item\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete an line item type.
 */
class CommerceLineItemTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a new CommerceLineItemTypeDeleteForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $queryFactory
   *   The entity query object.
   */
  public function __construct(QueryFactory $queryFactory) {
    $this->queryFactory = $queryFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the line item type %type?', array('%type' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.commerce_line_item_type.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $numOrders = $this->queryFactory->get('commerce_line_item')
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($numOrders) {
      $caption = '<p>' . $this->formatPlural($numOrders, '%type is used by 1 line item on your site. You can not remove this line item type until you have removed all of the %type line items.', '%type is used by @count line items on your site. You may not remove %type until you have removed all of the %type line items.', array('%type' => $this->entity->label())) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = array('#markup' => $caption);
      return $form;
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->entity->delete();
      $form_state->setRedirectUrl($this->getCancelUrl());
      drupal_set_message($this->t('Line item type %label has been deleted.', array('%label' => $this->entity->label())));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Line item type %label could not be deleted.', array('%label' => $this->entity->label())), 'error');
      $this->logger('commerce_line_item')->error($e);
    }
  }

}
