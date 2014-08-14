<?php

/**
 * @file
 * Contains \Drupal\example\Form\ExampleDeleteForm.
 */

namespace Drupal\commerce\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Builds the form to delete a store type.
 */
class CommerceStoreTypeDeleteForm extends EntityConfirmFormBase {

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
    return $this->t('Are you sure you want to delete %label?', array('%label' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('commerce.store_type_list');
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
    $num_stores = $this->database->query("SELECT COUNT(*) FROM {commerce_store} WHERE type = :type", array(':type' => $this->entity->id()))
      ->fetchField();
    if ($num_stores) {
      $caption = '<p>' . $this->formatPlural($num_stores, '%type is used by 1 store on your site. You can not remove this store type until you have removed all of the %type stores.', '%type is used by @count stores on your site. You may not remove %type until you have removed all of the %type stores.', array('%type' => $this->entity->label())) . '</p>';

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
    try {
      $this->entity->delete();
      drupal_set_message($this->t('Store type %label has been deleted.', array('%label' => $this->entity->label())));
    } catch (\Exception $e) {
      drupal_set_message($this->t('Store type %label could not be deleted.', array('%label' => $this->entity->label())));
      $this->logger('commerce')->error($e);
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
