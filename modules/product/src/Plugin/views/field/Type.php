<?php

/**
 * @file
 * Definition of Drupal\commerce_product\Plugin\views\field\Type.
 */

namespace Drupal\commerce_product\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_product\Plugin\views\field\CommerceProduct;
use Drupal\views\ResultRow;

/**
 * Field handler to translate a commerce product type into its readable form.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_product_type")
 */
class Type extends CommerceProduct {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['machine_name'] = array('default' => FALSE, 'bool' => TRUE);
    $options['link_to_commerce_product'] = array('default' => FALSE, 'bool' => TRUE);


    return $options;
  }

  /**
   * Provide machine_name option for to commerce product type display.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['machine_name'] = array(
      '#title' => $this->t('Output machine name'),
      '#description' => $this->t('Display field as the content type machine name.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['machine_name']),
    );
  }

  /**
   * Render commerce product type as human readable name, unless using machine_name option.
   */
  function render_name($data, $values) {
    if ($this->options['machine_name'] != 1 && $data !== NULL && $data !== '') {
      $type = entity_load('commerce_product_type', $data);
      return $type ? $this->t($this->sanitizeValue($type->label())) : '';
    }
    return $this->sanitizeValue($data);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->render_name($value, $values), $values);
  }

}
