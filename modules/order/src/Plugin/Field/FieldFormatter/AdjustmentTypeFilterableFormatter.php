<?php

namespace Drupal\commerce_order\Plugin\Field\FieldFormatter;

use Drupal\commerce_price\NumberFormatterFactoryInterface;
use Drupal\commerce_order\AdjustmentTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use CommerceGuys\Intl\Formatter\NumberFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'commerce_price_default' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_adjustment_type_filterable",
 *   label = @Translation("Type-filterable"),
 *   field_types = {
 *     "commerce_adjustment"
 *   }
 * )
 */
class AdjustmentTypeFilterableFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

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
   * The adjustment type manager.
   *
   * @var \Drupal\commerce_order\AdjustmentTypeManager
   */
  protected $adjustmentTypeManager;

  /**
   * The adjustment type labels keyed by id.
   *
   * @var array
   */
  protected $adjustmentTypes;

  /**
   * Constructs a new PriceDefaultFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_price\NumberFormatterFactoryInterface $number_formatter_factory
   *   The number formatter factory.
   * @param \Drupal\commerce_order\AdjustmentTypeManager $adjustment_type_manager
   *   The adjustment type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, NumberFormatterFactoryInterface $number_formatter_factory, AdjustmentTypeManager $adjustment_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->currencyStorage = $entity_type_manager->getStorage('commerce_currency');
    $this->numberFormatter = $number_formatter_factory->createInstance();
    $this->numberFormatter->setMaximumFractionDigits(6);
    if ($this->getSetting('strip_trailing_zeroes')) {
      $this->numberFormatter->setMinimumFractionDigits(0);
    }
    if ($this->getSetting('display_currency_code')) {
      $this->numberFormatter->setCurrencyDisplay(NumberFormatterInterface::CURRENCY_DISPLAY_CODE);
    }
    $this->adjustmentTypeManager = $adjustment_type_manager;
    foreach ($this->adjustmentTypeManager->getDefinitions() as $type) {
      $this->adjustmentTypes[$type['id']] = $type['label'];
    }
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('commerce_price.number_formatter_factory'),
      $container->get('plugin.manager.commerce_adjustment_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display_label' => FALSE,
      'filter_type' => [],
      'strip_trailing_zeroes' => FALSE,
      'display_currency_code' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    $elements['display_label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display adjustment label'),
      '#default_value' => $this->getSetting('display_label'),
    ];
    $elements['filter_type'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Filter by adjustment type'),
      '#default_value' => $this->getSetting('filter_type'),
      '#options' => $this->adjustmentTypes,
    ];
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
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('display_label')) {
      $summary[] = $this->t('Display label');
    }
    else {
      $summary[] = $this->t('Do not display label');
    }
    if (!empty($this->getSetting('filter_type'))) {
      $summary[] = $this->t('Filter by %types', [
        '%types' => implode(', ', $this->getSetting('filter_type')),
      ]);
    }
    else {
      $summary[] = $this->t('Do not filter by type');
    }
    if ($this->getSetting('strip_trailing_zeroes')) {
      $summary[] = $this->t('Strip trailing zeroes after the decimal point.');
    }
    else {
      $summary[] = $this->t('Do not strip trailing zeroes after the decimal point.');
    }
    if ($this->getSetting('display_currency_code')) {
      $summary[] = $this->t('Display the currency code instead of the currency symbol.');
    }
    else {
      $summary[] = $this->t('Display the currency symbol.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $currency_codes = [];
    foreach ($items as $delta => $item) {
      $adjustment = $item->value;
      $amount = $adjustment->getAmount();
      $currency_codes[] = $amount->getCurrencyCode();
    }
    $currencies = $this->currencyStorage->loadMultiple($currency_codes);

    $elements = [];
    $filters = [];
    foreach ($this->getSetting('filter_type') as $id => $value) {
      if ($id === $value) {
        $filters[] = $id;
      }
    }
    foreach ($items as $delta => $item) {
      /** @var \Drupal\commerce_order\Adjustment $adjustment */
      $adjustment = $item->value;
      if (!empty($filters) && !in_array($adjustment->getType(), $filters)) {
        continue;
      }
      $amount = $adjustment->getAmount();
      $currency = $currencies[$amount->getCurrencyCode()];
      $elements[$delta] = [
        '#markup' => $this->numberFormatter->formatCurrency($amount->getNumber(), $currency),
        '#cache' => [
          'contexts' => [
            'languages:' . LanguageInterface::TYPE_INTERFACE,
            'country',
          ],
        ],
      ];
      if ($this->getSetting('display_label')) {
        $elements[$delta]['#markup'] .= " ({$adjustment->getLabel()})";
      }
    }

    return $elements;
  }

}
