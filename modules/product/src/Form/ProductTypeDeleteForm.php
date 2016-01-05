<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Form\ProductTypeDeleteForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete a product type.
 */
class ProductTypeDeleteForm extends EntityDeleteForm {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a new ProductTypeDeleteForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *    The entity query object.
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
    $product_count = $this->queryFactory->get('commerce_product')
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($product_count) {
      $caption = '<p>' . $this->formatPlural($product_count, '%type is used by 1 product on your site. You can not remove this product type until you have removed all of the %type products.', '%type is used by @count products on your site. You may not remove %type until you have removed all of the %type products.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
