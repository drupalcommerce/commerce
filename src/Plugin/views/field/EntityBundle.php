<?php

/**
 * @file
 * Contains \Drupal\commerce\Plugin\views\field\EntityBundle.
 */

namespace Drupal\commerce\Plugin\views\field;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\Field;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays the entity bundle.
 *
 * Can be configured to show nothing when there's only one possible bundle,
 * allowing the 'Hide empty column' table setting to hide the column.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_entity_bundle")
 */
class EntityBundle extends Field {

  /**
   * The number of available bundles.
   *
   * @var int
   */
  protected $bundleCount;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $bundles = $this->entityManager->getBundleInfo($this->getEntityType());
    $this->bundleCount = count($bundles);
  }

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
      '#title' => t('Hide if there\'s only one bundle.'),
      '#default_value' => $this->options['hide_single_bundle'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderItems($items) {
    if ($this->options['hide_single_bundle'] && $this->bundleCount < 2) {
      return '';
    }
    return parent::renderItems($items);
  }

}
