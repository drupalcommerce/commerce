<?php

/**
 * @file
 * Contains \Drupal\example\Form\ExampleDeleteForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete a product type.
 */
class CommerceProductTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a new CommerceProductTypeDeleteForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   * The entity query object.
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
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %label?', array('%label' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('commerce_product.product_type_list');
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
    $num_products = $this->queryFactory->get('commerce_product')
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($num_products) {
      $caption = '<p>' . $this->formatPlural($num_products, '%type is used by 1 product on your site. You can not remove this product type until you have removed all of the %type products.', '%type is used by @count products on your site. You may not remove %type until you have removed all of the %type products.', array('%type' => $this->entity->label())) . '</p>';

      $form['#title'] = $this->getQuestion();
      $form['description'] = array('#markup' => $caption);
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('Store type %label has been deleted.', array('%label' => $this->entity->label())));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
