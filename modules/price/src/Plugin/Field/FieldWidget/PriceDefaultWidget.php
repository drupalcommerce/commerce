<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Plugin\Field\FieldWidget\PriceDefaultWidget.
 */

namespace Drupal\commerce_price\Plugin\Field\FieldWidget;

use Drupal\commerce_price\NumberFormatterFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
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
   * @param string $pluginId
   *   The plugin_id for the widget.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $thirdPartySettings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\commerce_price\NumberFormatterFactoryInterface $numberFormatterFactory
   *   The number formatter factory.
   */
  public function __construct($pluginId, $pluginDefinition, FieldDefinitionInterface $fieldDefinition, array $settings, array $thirdPartySettings, EntityManagerInterface $entityManager, NumberFormatterFactoryInterface $numberFormatterFactory) {
    parent::__construct($pluginId, $pluginDefinition, $fieldDefinition, $settings, $thirdPartySettings);

    $this->currencyStorage = $entityManager->getStorage('commerce_currency');
    $this->numberFormatter = $numberFormatterFactory->createInstance(NumberFormatterInterface::DECIMAL);
    $this->numberFormatter->setMinimumFractionDigits(0);
    $this->numberFormatter->setMaximumFractionDigits(6);
    $this->numberFormatter->setGroupingUsed(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $pluginId,
      $pluginDefinition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity.manager'),
      $container->get('commerce_price.number_formatter_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $formState) {
    // Load both enabled and disabled currencies, prices with disabled
    // currencies can be skipped down the line.
    $currencies = $this->currencyStorage->loadMultiple();
    $currencyCodes = array_keys($currencies);
    // Stop rendering if there are no currencies available.
    if (empty($currencyCodes)) {
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
    if (count($currencyCodes) == 1) {
      $lastVisibleElement = 'amount';
      $currencyCode = reset($currencyCodes);
      $element['amount']['#field_suffix'] = $currencyCode;
      $element['currency_code'] = [
        '#type' => 'value',
        '#value' => $currencyCode,
      ];
    }
    else {
      $lastVisibleElement = 'currency_code';
      $element['currency_code'] = [
        '#type' => 'select',
        '#title' => $this->t('Currency'),
        '#default_value' => $items[$delta]->currency_code,
        '#options' => array_combine($currencyCodes, $currencyCodes),
        '#title_display' => 'invisible',
        '#field_suffix' => '',
      ];
    }
    // Add the help text if specified.
    if (!empty($element['#description'])) {
      $element[$lastVisibleElement]['#field_suffix'] .= '<div class="description">' . $element['#description'] . '</div>';
    }

    return $element;
  }

  /**
   * Converts the amount back to the standard format (e.g. "9,99" -> "9.99").
   */
  public static function validateElement(array $element, FormStateInterface $formState) {
    // @todo Fix this.
    $currencyStorage = \Drupal::service('entity.manager')->getStorage('commerce_currency');
    $numberFormatter = \Drupal::service('commerce_price.number_formatter_factory')->createInstance();

    $value = $formState->getValue($element['#parents']);
    if (empty($value['amount'])) {
      return;
    }

    $currency = $currencyStorage->load($value['currency_code']);
    $value['amount'] = $numberFormatter->parseCurrency($value['amount'], $currency);
    if ($value['amount'] === FALSE) {
      $formState->setError($element['amount'], t('%title is not numeric.', [
        '%title' => $element['#title'],
      ]));
      return;
    }

    $formState->setValueForElement($element, $value);
  }

}
