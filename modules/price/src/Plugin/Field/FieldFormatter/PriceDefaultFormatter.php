<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Plugin\field\formatter\PriceDefaultFormatter.
 */

namespace Drupal\commerce_price\Plugin\Field\FieldFormatter;

use Drupal\commerce_price\NumberFormatterFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use CommerceGuys\Intl\Formatter\NumberFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'price_default' formatter.
 *
 * @FieldFormatter(
 *   id = "price_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "price"
 *   }
 * )
 */
class PriceDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new PriceDefaultFormatter object.
   *
   * @param string $pluginId
   *   The plugin_id for the formatter.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $viewMode
   *   The view mode.
   * @param array $thirdPartySettings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\commerce_price\NumberFormatterFactoryInterface $numberFormatterFactory
   *   The number formatter factory.
   */
  public function __construct($pluginId, $pluginDefinition, FieldDefinitionInterface $fieldDefinition, array $settings, $label, $viewMode, array $thirdPartySettings, EntityManagerInterface $entityManager, NumberFormatterFactoryInterface $numberFormatterFactory) {
    parent::__construct($pluginId, $pluginDefinition, $fieldDefinition, $settings, $label, $viewMode, $thirdPartySettings);

    $this->currencyStorage = $entityManager->getStorage('commerce_currency');
    $this->numberFormatter = $numberFormatterFactory->createInstance();
    $this->numberFormatter->setMaximumFractionDigits(6);
    if ($this->getSetting('strip_trailing_zeroes')) {
      $this->numberFormatter->setMinimumFractionDigits(0);
    }
    if ($this->getSetting('display_currency_code')) {
      $this->numberFormatter->setCurrencyDisplay(NumberFormatterInterface::CURRENCY_DISPLAY_CODE);
    }
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity.manager'),
      $container->get('commerce_price.number_formatter_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'strip_trailing_zeroes' => FALSE,
      'display_currency_code' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    $elements['strip_trailing_zeroes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strip trailing zeroes after the decimal point.'),
      '#default_value' => $this->getSetting('strip_trailing_zeroes'),
    ];
    $elements['display_currency_code'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display the currency code instead of the currency symbol.'),
      '#default_value' => $this->getSetting('display_currency_code'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $currencyCodes = [];
    foreach ($items as $delta => $item) {
      $currencyCodes[] = $item->currency_code;
    }
    $currencies = $this->currencyStorage->loadMultiple($currencyCodes);

    $elements = [];
    foreach ($items as $delta => $item) {
      $currency = $currencies[$item->currency_code];
      $elements[$delta] = [
        '#markup' => $this->numberFormatter->formatCurrency($item->amount, $currency),
        '#cache' => [
          'contexts' => [
            'languages:' . LanguageInterface::TYPE_INTERFACE,
            'country',
          ],
        ],
      ];
    }

    return $elements;
  }

}
