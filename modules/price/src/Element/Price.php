<?php

namespace Drupal\commerce_price\Element;

use CommerceGuys\Intl\Formatter\NumberFormatterInterface;
use Drupal\commerce_price\Price as PriceValue;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a price form element.
 *
 * Usage example:
 * @code
 * $form['number'] = [
 *   '#type' => 'commerce_price',
 *   '#title' => $this->t('number'),
 *   '#default_value' => new Price('99.99', 'USD'),
 *   '#size' => 60,
 *   '#maxlength' => 128,
 *   '#required' => TRUE,
 * ];
 * @endcode
 * Note:
 * $form_state->getValue('number') will be an array.
 * Use $form['number']['#value'] to get the price object.
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
      '#size' => 10,
      '#maxlength' => 128,
      '#default_value' => NULL,
      '#attached' => [
        'library' => ['commerce_price/admin'],
      ],
      '#element_validate' => [
        [$class, 'validateElement'],
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
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Ensure we have all possible values before creating price object.
    if ($input !== FALSE && $input !== NULL && isset($input['number']) && isset($input['currency_code'])) {
      // Convert empty string value to numeric value.
      if ($input['number'] === '') {
        $input['number'] = '0';
      }
      return new PriceValue($input['number'], $input['currency_code']);
    }
    return NULL;
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
  public static function processElement(array $element, FormStateInterface $form_state, &$complete_form) {
    if (!empty($element['#default_value']) && !($element['#default_value'] instanceof PriceValue)) {
      throw new \InvalidArgumentException('The #default_value for a commerce_price element must be an instance of \Drupal\commerce_price\Price.');
    }

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $currency_storage */
    $currency_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_currency');
    /** @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface $number_formatter */
    $number_formatter = \Drupal::service('commerce_price.number_formatter_factory')->createInstance(NumberFormatterInterface::DECIMAL);
    $number_formatter->setMaximumFractionDigits(6);
    $number_formatter->setGroupingUsed(FALSE);

    /** @var \Drupal\commerce_price\Entity\CurrencyInterface[] $currencies */
    $currencies = $currency_storage->loadMultiple();
    $currency_codes = array_keys($currencies);
    // Stop rendering if there are no currencies available.
    if (empty($currency_codes)) {
      return $element;
    }
    $fraction_digits = [];
    foreach ($currencies as $currency) {
      $fraction_digits[] = $currency->getFractionDigits();
    }
    $number_formatter->setMinimumFractionDigits(min($fraction_digits));

    $number = NULL;
    /** @var \Drupal\commerce_price\Price $default_price */
    $default_price = $element['#default_value'];
    if (!empty($default_price)) {
      // Convert the stored amount to the local format. For example, "9.99"
      // becomes "9,99" in many locales. This also strips any extra zeroes,
      // as configured via $this->numberFormatter->setMinimumFractionDigits().
      $number = $number_formatter->format($default_price->getNumber());
    }

    $element['number'] = [
      '#type' => 'textfield',
      '#title' => $element['#title'],
      '#default_value' => $number,
      '#required' => $element['#required'],
      '#size' => $element['#size'],
      '#maxlength' => $element['#maxlength'],
      // Provide an example to the end user so that they know which decimal
      // separator to use. This is the same pattern Drupal core uses.
      '#placeholder' => $number_formatter->format('9.99'),
    ];
    unset($element['#size']);
    unset($element['#maxlength']);

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
        '#default_value' => $default_price ? $default_price->getCurrencyCode() : NULL,
        '#options' => array_combine($currency_codes, $currency_codes),
        '#title_display' => 'invisible',
        '#field_suffix' => '',
      ];
    }
    // Add the help text if specified.
    if (!empty($element['#description'])) {
      $element[$last_visible_element]['#field_suffix'] .= '<div class="description">' . $element['#description'] . '</div>';
    }

    return $element;
  }

  /**
   * Converts the amount back to the standard format (e.g. "9,99" -> "9.99").
   *
   * @param array $element
   *   The commerce_price form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $currency_storage */
    $currency_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_currency');
    /** @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface $number_formatter */
    $number_formatter = \Drupal::service('commerce_price.number_formatter_factory')->createInstance();

    $value = $form_state->getValue($element['#parents']);
    if (empty($value['number'])) {
      return;
    }

    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency */
    $currency = $currency_storage->load($value['currency_code']);
    $value['number'] = $number_formatter->parseCurrency($value['number'], $currency);
    if ($value['number'] === FALSE) {
      $form_state->setError($element['number'], t('%title is not numeric.', [
        '%title' => $element['#title'],
      ]));
      return;
    }

    $form_state->setValueForElement($element, $value);
  }

}
