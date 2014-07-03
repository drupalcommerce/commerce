<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Form\CommerceOrderDeleteForm.
 */

namespace Drupal\commerce_order\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting an order.
 */
class CommerceOrderDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * Constructs a CommerceOrderDeleteForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    parent::__construct($entity_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the order %order_label?', array('%order_label' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return new Url('commerce_order.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    try {
      $this->entity->delete();
      $order_type_storage = $this->entityManager->getStorage('commerce_order_type');
      $order_type = $order_type_storage->load($this->entity->bundle())->label();
      $form_state['redirect_route'] = $this->getCancelRoute();
      drupal_set_message($this->t('@type %order_label has been deleted.', array('@type' => $order_type, '%order_label' => $this->entity->label())));
      watchdog('commerce_order', '@type: deleted %order_label.', array('@type' => $this->entity->bundle(), '%order_label' => $this->entity->label()));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The order %order_label could not be deleted.', array('%order_label' => $this->entity->label())), 'error');
      watchdog_exception('commerce_order', $e);
    }
  }

}
