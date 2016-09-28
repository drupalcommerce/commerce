<?php

namespace Drupal\commerce_price\Plugin\Field\FieldFormatter;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\NumberFormatterFactoryInterface;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
 */
class PriceCalculatedFormatter extends PriceDefaultFormatter implements ContainerFactoryPluginInterface {

  /**
   * The chain price resolver.
   *
   * @var \Drupal\commerce_price\Resolver\ChainPriceResolverInterface
   */
  protected $chainPriceResolver;

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
   * @param \Drupal\commerce_price\Resolver\ChainPriceResolverInterface $chain_price_resolver
   *   The chain price resolver.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, NumberFormatterFactoryInterface $number_formatter_factory, ChainPriceResolverInterface $chain_price_resolver) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $entity_type_manager, $number_formatter_factory);
    $this->chainPriceResolver = $chain_price_resolver;
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
      $container->get('commerce_price.chain_price_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $currency_codes = [];
    foreach ($items as $delta => $item) {
      $currency_codes[] = $item->currency_code;
    }
    $currencies = $this->currencyStorage->loadMultiple($currency_codes);

    $elements = [];
    /** @var \Drupal\commerce_price\Plugin\Field\FieldType\PriceItem $item */
    foreach ($items as $delta => $item) {
      /** @var \Drupal\commerce\PurchasableEntityInterface $purchasable_entity */
      $purchasable_entity = $items->getEntity();
      $resolved_price = $this->chainPriceResolver->resolve($purchasable_entity, '1');
      $number = $resolved_price->getNumber();
      $currency = $currencies[$resolved_price->getCurrencyCode()];

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
    return $entity_type->isSubclassOf(PurchasableEntityInterface::class);
  }

}
