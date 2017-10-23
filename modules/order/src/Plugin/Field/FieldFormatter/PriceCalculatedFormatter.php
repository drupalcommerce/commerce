<?php

namespace Drupal\commerce_order\Plugin\Field\FieldFormatter;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\AdjustmentTypeManager;
use Drupal\commerce_order\PurchasableEntityPriceCalculator;
use Drupal\commerce_order\PurchasableEntityPriceCalculatorInterface;
use Drupal\commerce_price\NumberFormatterFactoryInterface;
use Drupal\commerce_price\Plugin\Field\FieldFormatter\PriceDefaultFormatter;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'commerce_price_calculated' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_price_calculated",
 *   label = @Translation("Calculated price"),
 *   field_types = {
 *     "commerce_price"
 *   }
 * )
 *
 * @todo How are we going to handle plugin ID name change?
 */
class PriceCalculatedFormatter extends PriceDefaultFormatter implements ContainerFactoryPluginInterface {

  /**
   * The price calculator.
   *
   * @var \Drupal\commerce_order\PurchasableEntityPriceCalculatorInterface
   */
  protected $priceCalculator;

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

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
   * The adjustment type manager.
   *
   * @var \Drupal\commerce_order\AdjustmentTypeManager
   */
  protected $adjustmentTypeManager;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_price\NumberFormatterFactoryInterface $number_formatter_factory
   *   The number formatter factory.
   * @param \Drupal\commerce_order\PurchasableEntityPriceCalculatorInterface $purchasable_entity_price_calculator
   *   The chain price resolver.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\commerce_order\AdjustmentTypeManager $adjustment_type_manager
   *   The adjustment type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, NumberFormatterFactoryInterface $number_formatter_factory, PurchasableEntityPriceCalculatorInterface $purchasable_entity_price_calculator, CurrentStoreInterface $current_store, AccountInterface $current_user, AdjustmentTypeManager $adjustment_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $entity_type_manager, $number_formatter_factory);

    $this->priceCalculator = $purchasable_entity_price_calculator;
    $this->currentStore = $current_store;
    $this->currencyStorage = $entity_type_manager->getStorage('commerce_currency');
    $this->currentUser = $current_user;
    $this->adjustmentTypeManager = $adjustment_type_manager;
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
      $container->get('commerce_order.purchasable_entity_price_calculator'),
      $container->get('commerce_store.current_store'),
      $container->get('current_user'),
      $container->get('plugin.manager.commerce_adjustment_type')
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
    $elements =  parent::settingsForm($form, $form_state);

    $elements['adjustment_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Adjustment types'),
      '#options' => [],
      '#default_value' => $this->getSetting('adjustment_types'),
    ];

    foreach ($this->adjustmentTypeManager->getDefinitions() as $plugin_id => $definition) {
      $elements['adjustment_types']['#options'][$plugin_id] = $definition['label'];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $adjustment_types = $this->getSetting('adjustment_types');
    if (empty($adjustment_types)) {
      $summary[] = $this->t('No included adjustment types');
    }
    else {
      $adjustment_type_labels = [];
      foreach ($adjustment_types as $adjustment_type) {
        $definition = $this->adjustmentTypeManager->getDefinition($adjustment_type);
        $adjustment_type_labels[] = $definition['label'];
      }
      $summary[] = $this->t('Included adjustment types: @types', ['@types' => implode(', ', $adjustment_type_labels)]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    /** @var \Drupal\commerce_price\Plugin\Field\FieldType\PriceItem $item */
    foreach ($items as $delta => $item) {
      /** @var \Drupal\commerce\PurchasableEntityInterface $purchasable_entity */
      $purchasable_entity = $items->getEntity();
      $calculated_price = $this->priceCalculator->calculate($purchasable_entity, 1, $this->getSetting('adjustment_types'));
      $number = $calculated_price['calculated']->getNumber();
      $currency = $this->currencyStorage->load($calculated_price['calculated']->getCurrencyCode());

      $elements[$delta] = [
        '#markup' => $this->numberFormatter->formatCurrency($number, $currency),
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
