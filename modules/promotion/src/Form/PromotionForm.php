<?php

namespace Drupal\commerce_promotion\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Defines the promotion add/edit form.
 */
class PromotionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Skip building the form if there are no available stores.
    $store_query = $this->entityManager->getStorage('commerce_store')->getQuery();
    if ($store_query->count()->execute() == 0) {
      $link = Link::createFromRoute('Add a new store.', 'entity.commerce_store.add_page');
      $form['warning'] = [
        '#markup' => t("Promotions can't be created until a store has been added. @link", ['@link' => $link->toString()]),
      ];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_promotion\Entity\Promotion $promotion */
    $promotion = $this->entity;
    $form = parent::form($form, $form_state);

    $form['#tree'] = TRUE;
    $form['#theme'] = ['commerce_promotion_form'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_promotion\Entity\Promotion $promotion */
    $promotion = $this->getEntity();
    $promotion->save();
    drupal_set_message($this->t('The promotion %label has been successfully saved.', ['%label' => $promotion->label()]));
    $form_state->setRedirect('entity.commerce_promotion.collection', ['commerce_promotion' => $promotion->id()]);
  }

}
