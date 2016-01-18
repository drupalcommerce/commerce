<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Plugin\views\field\Store.
 */

namespace Drupal\commerce_store\Plugin\views\field;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\Field;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays the store.
 *
 * Can be configured to show nothing when there's only one possible store,
 * allowing the 'Hide empty column' table setting to hide the column.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_store")
 */
class Store extends Field {

  /**
   * The number of available stores.
   *
   * @var int
   */
  protected $storeCount;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $store_query = $this->entityManager->getStorage('commerce_store')->getQuery();
    $this->storeCount = $store_query->count()->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['hide_single_store'] = ['default' => TRUE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['hide_single_store'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide if there\'s only one store.'),
      '#default_value' => $this->options['hide_single_store'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderItems($items) {
    if ($this->options['hide_single_store'] && $this->storeCount < 2) {
      return '';
    }
    return parent::renderItems($items);
  }

}
