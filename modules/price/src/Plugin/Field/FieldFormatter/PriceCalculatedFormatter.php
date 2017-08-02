<?php

namespace Drupal\commerce_price\Plugin\Field\FieldFormatter;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\NumberFormatterFactoryInterface;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\commerce_store\StoreContextInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
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
 */
class PriceCalculatedFormatter extends PriceDefaultFormatter implements ContainerFactoryPluginInterface {

  /**
   * The chain price resolver.
   *
   * @var \Drupal\commerce_price\Resolver\ChainPriceResolverInterface
   */
  protected $chainPriceResolver;

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
   * The store context.
   *
   * @var \Drupal\commerce_store\StoreContextInterface
   */
  protected $storeContext;

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
   * @param \Drupal\commerce_store\StoreContextInterface $store_context
   *   The store context.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, NumberFormatterFactoryInterface $number_formatter_factory, ChainPriceResolverInterface $chain_price_resolver, StoreContextInterface $store_context, AccountInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $entity_type_manager, $number_formatter_factory);

    $this->chainPriceResolver = $chain_price_resolver;
    $this->storeContext = $store_context;
    $this->currencyStorage = $entity_type_manager->getStorage('commerce_currency');
    $this->currentUser = $current_user;
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
      $container->get('commerce_price.chain_price_resolver'),
      $container->get('commerce_store.store_context'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $store = $this->storeContext->getStore();
    $context = new Context($this->currentUser, $store);
    $elements = [];
    /** @var \Drupal\commerce_price\Plugin\Field\FieldType\PriceItem $item */
    foreach ($items as $delta => $item) {
      /** @var \Drupal\commerce\PurchasableEntityInterface $purchasable_entity */
      $purchasable_entity = $items->getEntity();
      $resolved_price = $this->chainPriceResolver->resolve($purchasable_entity, 1, $context);
      $number = $resolved_price->getNumber();
      $currency = $this->currencyStorage->load($resolved_price->getCurrencyCode());

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
