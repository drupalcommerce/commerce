<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Form\StoreTypeDeleteForm.
 */

namespace Drupal\commerce_store\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete a store type.
 */
class StoreTypeDeleteForm extends EntityDeleteForm {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a new StoreTypeDeleteForm object.
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $numStores = $this->queryFactory->get('commerce_store')
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($numStores) {
      $caption = '<p>' . $this->formatPlural($numStores, '%type is used by 1 store on your site. You can not remove this store type until you have removed all of the %type stores.', '%type is used by @count stores on your site. You may not remove %type until you have removed all of the %type stores.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
