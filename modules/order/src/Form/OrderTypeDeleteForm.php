<?php

namespace Drupal\commerce_order\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete an order type.
 */
class OrderTypeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $order_count = $this->entityTypeManager->getStorage('commerce_order')->getQuery()
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($order_count) {
      $caption = '<p>' . $this->formatPlural($order_count, '%type is used by 1 order on your site. You can not remove this order type until you have removed all of the %type orders.', '%type is used by @count orders on your site. You may not remove %type until you have removed all of the %type orders.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
