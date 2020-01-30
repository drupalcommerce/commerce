<?php

namespace Drupal\commerce_promotion;

use Drupal\content_translation\ContentTranslationHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the translation handler for promotions.
 */
class PromotionTranslationHandler extends ContentTranslationHandler {

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(array &$form, FormStateInterface $form_state, EntityInterface $entity) {
    parent::entityFormAlter($form, $form_state, $entity);

    if (isset($form['content_translation'])) {
      $form['content_translation']['status']['#access'] = FALSE;
      $form['content_translation']['uid']['#access'] = FALSE;
      $form['content_translation']['created']['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityFormEntityBuild($entity_type, EntityInterface $entity, array $form, FormStateInterface $form_state) {
    if ($form_state->hasValue('content_translation')) {
      $translation = &$form_state->getValue('content_translation');
      /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $entity */
      $translation['status'] = $entity->isEnabled();
      $translation['uid'] = 0;
    }
    parent::entityFormEntityBuild($entity_type, $entity, $form, $form_state);
  }

}
