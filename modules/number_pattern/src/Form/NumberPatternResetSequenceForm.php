<?php

namespace Drupal\commerce_number_pattern\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a confirmation form for resetting a number pattern's sequence.
 */
class NumberPatternResetSequenceForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to reset the sequence for the %label number pattern?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Reset sequence');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_number_pattern\Entity\NumberPatternInterface $number_pattern */
    $number_pattern = $this->entity;
    /** @var \Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern\SequentialNumberPatternInterface $number_pattern_plugin */
    $number_pattern_plugin = $number_pattern->getPlugin();
    $number_pattern_plugin->resetSequence();
    $number_pattern->save();

    $this->messenger()->addMessage($this->t('The sequence for the %label number pattern has been reset.', [
      '%label' => $number_pattern->label(),
    ]));
    $form_state->setRedirectUrl($number_pattern->toUrl('collection'));
  }

}
