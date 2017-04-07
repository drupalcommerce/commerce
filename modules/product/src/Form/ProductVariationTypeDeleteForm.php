<?php

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete a product variation type.
 */
class ProductVariationTypeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $variation_count = $this->entityTypeManager->getStorage('commerce_product_variation')->getQuery()
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
