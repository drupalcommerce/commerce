<?php

/**
 * @file
 * Definition of Drupal\commerce_product\Plugin\views\field\CommerceProduct.
 */

namespace Drupal\commerce_product\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to provide simple renderer that allows linking to a product.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_product")
 */
class CommerceProduct extends FieldPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->options['link_to_commerce_product'])) {
      $this->additional_fields['product_id'] = 'product_id';
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_commerce_product'] = array('default' => TRUE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Provide link to commerce product option
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_commerce_product'] = array(
      '#title' => $this->t('Link this field to the commerce product'),
      '#description' => $this->t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_commerce_product'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Prepares a link to the commerce product.
   *
   * @param string $data
   *   The XSS safe string for the link text.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    // TODO: add permission check
    if (!empty($this->options['link_to_commerce_product']) && ($entity = $this->getEntity($values)) && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = $entity->getSystemPath();
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->sanitizeValue($value), $values);
  }

}
