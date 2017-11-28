<?php

namespace Drupal\commerce_price\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a price form element.
 *
 * Usage example:
 * @code
 * $form['amount'] = [
 *   '#type' => 'commerce_price',
 *   '#title' => $this->t('Amount'),
 *   '#default_value' => ['number' => '99.99', 'currency_code' => 'USD'],
 *   '#allow_negative' => FALSE,
 *   '#size' => 60,
 *   '#maxlength' => 128,
 *   '#required' => TRUE,
 *   '#available_currencies' => ['USD', 'EUR'],
 * ];
 * @endcode
 *
 * @FormElement("commerce_price")
 */
class Price extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      // List of currencies codes. If empty, all currencies will be available.
      '#available_currencies' => [],

      '#size' => 10,
      '#maxlength' => 128,
      '#default_value' => NULL,
      '#allow_negative' => FALSE,
      '#attached' => [
        'library' => ['commerce_price/admin'],
      ],
      '#process' => [
        [$class, 'processElement'],
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#input' => TRUE,
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Builds the commerce_price form element.
   *
   * @param array $element
   *   The initial commerce_price form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The built commerce_price form element.
   *
   * @throws \InvalidArgumentException
   *   Thrown when #default_value is not an instance of
   *   \Drupal\commerce_price\Price.
   */
  public static function processElement(array $element, FormStateInterface $form_state, array &$complete_form) {
    $default_value = $element['#default_value'];
    if (isset($default_value) && !self::validateDefaultValue($default_value)) {
      throw new \InvalidArgumentException('The #default_value for a commerce_price element must be an array with "number" and "currency_code" keys.');
    }

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $currency_storage */
    $currency_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_currency');
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface[] $currencies */
    $currencies = $currency_storage->loadMultiple();
    $currency_codes = array_keys($currencies);
    // Keep only available currencies.
    $available_currencies = $element['#available_currencies'];
    if (isset($available_currencies) && !empty($available_currencies)) {
      $currency_codes = array_intersect($currency_codes, $available_currencies);
    }
    // Stop rendering if there are no currencies available.
    if (empty($currency_codes)) {
      return $element;
    }
    $fraction_digits = [];
    foreach ($currencies as $currency) {
      $fraction_digits[] = $currency->getFractionDigits();
    }

    $element['#tree'] = TRUE;
    $element['#attributes']['class'][] = 'form-type-commerce-price';

    $element['number'] = [
      '#type' => 'commerce_number',
      '#title' => $element['#title'],
      '#title_display' => $element['#title_display'],
      '#default_value' => $default_value ? $default_value['number'] : NULL,
      '#required' => $element['#required'],
      '#size' => $element['#size'],
      '#maxlength' => $element['#maxlength'],
      '#min_fraction_digits' => min($fraction_digits),
      // '6' is the field storage maximum.
      '#max_fraction_digits' => 6,
      '#min' => $element['#allow_negative'] ? NULL : 0,
    ];
    if (isset($element['#ajax'])) {
      $element['number']['#ajax'] = $element['#ajax'];
    }

    if (count($currency_codes) == 1) {
      $last_visible_element = 'number';
      $currency_code = reset($currency_codes);
      $element['number']['#field_suffix'] = $currency_code;
      $element['currency_code'] = [
        '#type' => 'hidden',
        '#value' => $currency_code,
      ];
    }
    else {
      $last_visible_element = 'currency_code';
      $element['currency_code'] = [
        '#type' => 'select',
        '#title' => t('Currency'),
        '#default_value' => $default_value ? $default_value['currency_code'] : NULL,
        '#options' => array_combine($currency_codes, $currency_codes),
        '#title_display' => 'invisible',
        '#field_suffix' => '',
      ];
      if (isset($element['#ajax'])) {
        $element['currency_code']['#ajax'] = $element['#ajax'];
      }
    }
    // Add the help text if specified.
    if (!empty($element['#description'])) {
      $element[$last_visible_element]['#field_suffix'] .= '<div class="description">' . $element['#description'] . '</div>';
    }
    // Remove the keys that were transfered to child elements.
    unset($element['#size']);
    unset($element['#maxlength']);
    unset($element['#ajax']);

    return $element;
  }

  /**
   * Validates the default value.
   *
   * @param mixed $default_value
   *   The default value.
   *
   * @return bool
   *   TRUE if the default value is valid, FALSE otherwise.
   */
  public static function validateDefaultValue($default_value) {
    if (!is_array($default_value)) {
      return FALSE;
    }
    if (!array_key_exists('number', $default_value) || !array_key_exists('currency_code', $default_value)) {
      return FALSE;
    }
    return TRUE;
  }

}
