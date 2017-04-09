<?php

namespace Drupal\commerce_order\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete an order item type.
 */
class OrderItemTypeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $order_item_count = $this->entityTypeManager->getStorage('commerce_order_item')->getQuery()
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($order_item_count) {
      $caption = '<p>' . $this->formatPlural($order_item_count, '%type is used by 1 order item on your site. You can not remove this order item type until you have removed all of the %type order items.', '%type is used by @count order items on your site. You may not remove %type until you have removed all of the %type order items.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
