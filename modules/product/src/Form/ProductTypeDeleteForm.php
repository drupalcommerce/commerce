<?php

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete a product type.
 */
class ProductTypeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $product_count = $this->entityTypeManager->getStorage('commerce_product')->getQuery()
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
