<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Form\LineItemTypeDeleteForm.
 */

namespace Drupal\commerce_order\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete an line item type.
 */
class LineItemTypeDeleteForm extends EntityDeleteForm {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a new LineItemTypeDeleteForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query object.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->queryFactory = $query_factory;
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $line_item_count = $this->queryFactory->get('commerce_line_item')
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($line_item_count) {
      $caption = '<p>' . $this->formatPlural($line_item_count, '%type is used by 1 line item on your site. You can not remove this line item type until you have removed all of the %type line items.', '%type is used by @count line items on your site. You may not remove %type until you have removed all of the %type line items.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
