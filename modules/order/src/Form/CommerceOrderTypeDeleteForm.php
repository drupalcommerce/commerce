<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Form\CommerceOrderTypeDeleteForm.
 */

namespace Drupal\commerce_order\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete an order type.
 */
class CommerceOrderTypeDeleteForm extends EntityConfirmFormBase {
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
    return $this->t('Are you sure you want to delete the order type %type?', array('%type' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.commerce_order_type.list');
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
    $num_orders = $this->database->query("SELECT COUNT(*) FROM {commerce_order} WHERE type = :type", array(':type' => $this->entity->id()))
      ->fetchField();
    if ($num_orders) {
      $caption = '<p>' . $this->formatPlural($num_orders, '%type is used by 1 order on your site. You can not remove this order type until you have removed all of the %type orders.', '%type is used by @count orders on your site. You may not remove %type until you have removed all of the %type orders.', array('%type' => $this->entity->label())) . '</p>';

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
      $form_state->setRedirectUrl($this->getCancelUrl());
      drupal_set_message($this->t('Order type %label has been deleted.', array('%label' => $this->entity->label())));
    } catch (\Exception $e) {
      drupal_set_message($this->t('Order type %label could not be deleted.', array('%label' => $this->entity->label())), 'error');
      watchdog_exception('commerce_order', $e);
    }
  }

}
