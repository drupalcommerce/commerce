<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Plugin\Field\FieldWidget\PriceDefaultWidget.
 */

namespace Drupal\commerce_price\Plugin\Field\FieldWidget;

use Drupal\commerce_price\NumberFormatterFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use CommerceGuys\Intl\Formatter\NumberFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'price_default' widget.
 *
 * @FieldWidget(
 *   id = "price_default",
 *   label = @Translation("Price"),
 *   field_types = {
 *     "price"
 *   }
 * )
 */
class PriceDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * The number formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface
   */
  protected $numberFormatter;

  /**
   * Constructs a new PriceDefaultWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_price\NumberFormatterFactoryInterface $number_formatter_factory
   *   The number formatter factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, NumberFormatterFactoryInterface $number_formatter_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->currencyStorage = $entity_type_manager->getStorage('commerce_currency');
    $this->numberFormatter = $number_formatter_factory->createInstance(NumberFormatterInterface::DECIMAL);
    $this->numberFormatter->setMinimumFractionDigits(0);
    $this->numberFormatter->setMaximumFractionDigits(6);
    $this->numberFormatter->setGroupingUsed(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('commerce_price.number_formatter_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Load currencies.
    $currencies = $this->currencyStorage->loadMultiple();
    $currency_codes = array_keys($currencies);
    // Stop rendering if there are no currencies available.
    if (empty($currency_codes)) {
      return $element;
    }

    $amount = $items[$delta]->amount;
    if (!empty($amount)) {
      // Convert the stored amount to the local format. For example, "9.99"
      // becomes "9,99" in many locales. This also strips any extra zeroes,
      // as configured via $this->numberFormatter->setMinimumFractionDigits().
      $amount = $this->numberFormatter->format($amount);
    }

    $element['#attached']['library'][] = 'commerce_price/admin';
    $element['#element_validate'] = [
      [get_class($this), 'validateElement'],
    ];
    $element['amount'] = [
      '#type' => 'textfield',
      '#title' => $element['#title'],
      '#default_value' => $amount,
      '#required' => $element['#required'],
      '#size' => 10,
      '#maxlength' => 255,
      // Provide an example to the end user so that they know which decimal
      // separator to use. This is the same pattern Drupal core uses.
      '#placeholder' => $this->numberFormatter->format('9.99'),
    ];
    if (count($currency_codes) == 1) {
      $last_visible_element = 'amount';
      $currency_code = reset($currency_codes);
      $element['amount']['#field_suffix'] = $currency_code;
      $element['currency_code'] = [
        '#type' => 'value',
        '#value' => $currency_code,
      ];
    }
    else {
      $last_visible_element = 'currency_code';
      $element['currency_code'] = [
        '#type' => 'select',
        '#title' => $this->t('Currency'),
        '#default_value' => $items[$delta]->currency_code,
        '#options' => array_combine($currency_codes, $currency_codes),
        '#title_display' => 'invisible',
        '#field_suffix' => '',
      ];
    }
    // Add the help text if specified.
    // Replicates the commerce_field_widget_form_alter() logic because
    // the copied help text can't be reached by the alter.
    $base_field = $this->fieldDefinition instanceof BaseFieldDefinition;
    $has_override = $this->fieldDefinition->getSetting('display_description');
    $hide_description = $base_field && !$has_override;
    if (!empty($element['#description']) && !$hide_description) {
      $element[$last_visible_element]['#field_suffix'] .= '<div class="description">' . $element['#description'] . '</div>';
    }

    return $element;
  }

  /**
   * Converts the amount back to the standard format (e.g. "9,99" -> "9.99").
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $currency_storage */
    $currency_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_currency');
    /** @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface $number_formatter */
    $number_formatter = \Drupal::service('commerce_price.number_formatter_factory')->createInstance();

    $value = $form_state->getValue($element['#parents']);
    if (empty($value['amount'])) {
      return;
    }

    $currency = $currency_storage->load($value['currency_code']);
    $value['amount'] = $number_formatter->parseCurrency($value['amount'], $currency);
    if ($value['amount'] === FALSE) {
      $form_state->setError($element['amount'], t('%title is not numeric.', [
        '%title' => $element['#title'],
      ]));
      return;
    }

    $form_state->setValueForElement($element, $value);
  }

}
