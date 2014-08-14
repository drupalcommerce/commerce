<?php

/**
 * @file
 * Contains \Drupal\example\Form\ExampleDeleteForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete a store type.
 */
class CommerceProductTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new CommerceProductTypeDeleteForm object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the product type %type?', array('%type' => $this->entity->label()));
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
    $num_products = $this->database->query("SELECT COUNT(*) FROM {commerce_product} WHERE type = :type", array(':type' => $this->entity->id()))
      ->fetchField();
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
