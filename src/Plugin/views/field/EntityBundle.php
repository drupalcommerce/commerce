<?php

namespace Drupal\commerce\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityField;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Displays the entity bundle.
 *
 * Can be configured to show nothing when there's only one possible bundle.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_entity_bundle")
 */
class EntityBundle extends EntityField {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['hide_single_bundle'] = ['default' => TRUE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['hide_single_bundle'] = [
      '#type' => 'checkbox',
      '#title' => t("Hide if there's only one bundle."),
      '#default_value' => $this->options['hide_single_bundle'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $bundles = $this->entityManager->getBundleInfo($this->getEntityType());
    if ($this->options['hide_single_bundle'] && count($bundles) <= 1) {
      return FALSE;
    }

    return parent::access($account);
  }

}
