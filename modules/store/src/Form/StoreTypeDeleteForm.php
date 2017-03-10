<?php

namespace Drupal\commerce_store\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete a store type.
 */
class StoreTypeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $store_count = $this->entityTypeManager->getStorage('commerce_store')->getQuery()
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($store_count) {
      $caption = '<p>' . $this->formatPlural($store_count, '%type is used by 1 store on your site. You can not remove this store type until you have removed all of the %type stores.', '%type is used by @count stores on your site. You may not remove %type until you have removed all of the %type stores.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
