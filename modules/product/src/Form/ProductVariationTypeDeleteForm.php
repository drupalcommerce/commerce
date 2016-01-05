<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Form\ProductVariationTypeDeleteForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete a product variation type.
 */
class ProductVariationTypeDeleteForm extends EntityDeleteForm {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a new ProductVariationTypeDeleteForm object.
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
    $variation_count = $this->queryFactory->get('commerce_product_variation')
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($variation_count) {
      $caption = '<p>' . $this->formatPlural($variation_count, '%type is used by 1 product variation on your site. You can not remove this product variation type until you have removed all of the %type product variations.', '%type is used by @count product variations on your site. You may not remove %type until you have removed all of the %type product variations.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
