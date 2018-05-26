<?php

namespace Drupal\commerce\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\Bundle;
use Drupal\Core\Session\AccountInterface;

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
      '#title' => t("Hide if there's only one bundle."),
      '#default_value' => $this->options['expose']['hide_single_bundle'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $bundles = $this->entityManager->getBundleInfo($this->getEntityType());
    if ($this->options['expose']['hide_single_bundle'] && count($bundles) <= 1) {
      return FALSE;
    }

    return parent::access($account);
  }

  /**
   * {@inheritdoc}
   *
   * We override the parent method so that it does not cause an unhandled
   * PluginNotFoundException to be thrown, due to entities which use bundle
   * plugins.
   */
  public function calculateDependencies() {
    $parents_parent = get_parent_class(get_parent_class($this));
    $dependencies = $parents_parent::calculateDependencies();

    $bundle_entity_type = $this->entityType->getBundleEntityType();
    if ($bundle_entity_type) {
      $bundle_entity_storage = $this->entityManager->getStorage($bundle_entity_type);

      foreach (array_keys($this->value) as $bundle) {
        if ($bundle_entity = $bundle_entity_storage->load($bundle)) {
          $dependencies[$bundle_entity->getConfigDependencyKey()][] = $bundle_entity->getConfigDependencyName();
        }
      }
    }

    return $dependencies;
  }

}
