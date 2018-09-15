<?php

namespace Drupal\commerce_order\Plugin\Field\FieldFormatter;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\AdjustmentTypeManager;
use Drupal\commerce_order\PriceCalculatorInterface;
use Drupal\commerce_price\Plugin\Field\FieldFormatter\PriceDefaultFormatter;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alternative implementation of the 'commerce_price_calculated' formatter.
 *
 * @see \Drupal\commerce_price\Plugin\Field\FieldFormatter\PriceCalculatedFormatter
 */
class PriceCalculatedFormatter extends PriceDefaultFormatter implements ContainerFactoryPluginInterface {

  /**
   * The adjustment type manager.
   *
   * @var \Drupal\commerce_order\AdjustmentTypeManager
   */
  protected $adjustmentTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The price calculator.
   *
   * @var \Drupal\commerce_order\PriceCalculatorInterface
   */
  protected $priceCalculator;

  /**
   * Constructs a new PriceCalculatedFormatter object.
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
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter.
   * @param \Drupal\commerce_order\AdjustmentTypeManager $adjustment_type_manager
   *   The adjustment type manager.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\commerce_order\PriceCalculatorInterface $price_calculator
   *   The price calculator.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, CurrencyFormatterInterface $currency_formatter, AdjustmentTypeManager $adjustment_type_manager, CurrentStoreInterface $current_store, AccountInterface $current_user, PriceCalculatorInterface $price_calculator) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $currency_formatter);

    $this->adjustmentTypeManager = $adjustment_type_manager;
    $this->currentStore = $current_store;
    $this->currentUser = $current_user;
    $this->priceCalculator = $price_calculator;
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
      $container->get('commerce_price.currency_formatter'),
      $container->get('plugin.manager.commerce_adjustment_type'),
      $container->get('commerce_store.current_store'),
      $container->get('current_user'),
      $container->get('commerce_order.price_calculator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'adjustment_types' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['adjustment_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Adjustments'),
      '#options' => [],
      '#default_value' => $this->getSetting('adjustment_types'),
    ];
    foreach ($this->adjustmentTypeManager->getDefinitions() as $plugin_id => $definition) {
      if (!in_array($plugin_id, ['custom'])) {
        $label = $this->t('Apply @label to the calculated price', ['@label' => $definition['plural_label']]);
        $elements['adjustment_types']['#options'][$plugin_id] = $label;
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $enabled_adjustment_types = array_filter($this->getSetting('adjustment_types'));
    foreach ($this->adjustmentTypeManager->getDefinitions() as $plugin_id => $definition) {
      if (in_array($plugin_id, $enabled_adjustment_types)) {
        $summary[] = $this->t('Apply @label to the calculated price', ['@label' => $definition['plural_label']]);
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    if (!$items->isEmpty()) {
      $context = new Context($this->currentUser, $this->currentStore->getStore(), NULL, [
        'field_name' => $items->getName(),
      ]);
      /** @var \Drupal\commerce\PurchasableEntityInterface $purchasable_entity */
      $purchasable_entity = $items->getEntity();
      $adjustment_types = array_filter($this->getSetting('adjustment_types'));
      $result = $this->priceCalculator->calculate($purchasable_entity, 1, $context, $adjustment_types);
      $calculated_price = $result->getCalculatedPrice();
      $number = $calculated_price->getNumber();
      $currency_code = $calculated_price->getCurrencyCode();
      $options = $this->getFormattingOptions();

      $elements[0] = [
        '#markup' => $this->currencyFormatter->format($number, $currency_code, $options),
        '#cache' => [
          'tags' => $purchasable_entity->getCacheTags(),
          'contexts' => Cache::mergeContexts($purchasable_entity->getCacheContexts(), [
            'languages:' . LanguageInterface::TYPE_INTERFACE,
            'country',
          ]),
        ],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($field_definition->getTargetEntityTypeId());
    return $entity_type->entityClassImplements(PurchasableEntityInterface::class);
  }

}
