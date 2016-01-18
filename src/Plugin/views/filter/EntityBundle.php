<?php

/**
 * @file
 * Contains \Drupal\commerce\Plugin\views\filter\EntityBundle.
 */

namespace Drupal\commerce\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\Bundle;

/**
 * Filters by entity bundle.
 *
 * Can be configured to hide the exposed form when there's only one possible
 * bundle.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("commerce_entity_bundle")
 */
class EntityBundle extends Bundle {

  /**
   * {@inheritdoc}
   */
  public function defaultExposeOptions() {
    parent::defaultExposeOptions();
    $this->options['expose']['hide_single_bundle'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['expose']['contains']['hide_single_bundle'] = ['default' => TRUE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['expose']['hide_single_bundle'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide if there\'s only one bundle.'),
      '#default_value' => $this->options['expose']['hide_single_bundle'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isExposed() {
    if (!empty($this->options['exposed'])) {
      $entity_type = $this->getEntityType();
      $bundles = $this->entityManager->getBundleInfo($entity_type);
      if (count($bundles) > 1 || empty($this->options['expose']['hide_single_bundle'])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitExposed(&$form, FormStateInterface $form_state) {
    $filter_value = $form_state->getValue($this->options['id']);
    if (empty($filter_value)) {
      // The exposed form is submitted on first view load, even when this
      // filter is hidden. To prevent a notice, a default value is provided.
      $form_state->setValue($this->options['id'], 'All');
    }
  }

}
