<?php

namespace Drupal\commerce_product\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\PercentageOffBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Product: Percentage off' offer.
 *
 * @CommercePromotionOffer(
 *   id = "commerce_promotion_product_percentage_off",
 *   label = @Translation("Percentage off each product in the order"),
 * )
 */
class ProductPercentageOff extends PercentageOffBase {

  /**
   * The product storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productStorage;

  /**
   * Constructs a new ProductPercentageOff object.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The rounder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RounderInterface $rounder, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $rounder);

    $this->productStorage = $entity_type_manager->getStorage('commerce_product');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_price.rounder'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'product_id' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $product_id = $this->configuration['product_id'];
    if (empty($product_id)) {
      return;
    }

    foreach ($this->getOrder()->getItems() as $order_item) {
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      $purchasable_entity = $order_item->getPurchasedEntity();
      if (!$purchasable_entity || $purchasable_entity->getEntityTypeId() != 'commerce_product_variation') {
        continue;
      }

      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchasable_entity */
      if ($purchasable_entity->getProductId() == $product_id) {
        $adjustment_amount = $order_item->getUnitPrice()->multiply($this->getAmount());
        $adjustment_amount = $this->rounder->round($adjustment_amount);
        $this->applyAdjustment($order_item, $adjustment_amount);
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $product = NULL;
    if (!empty($this->configuration['product_id'])) {
      $product = $this->productStorage->load($this->configuration['product_id']);
    }
    $form['product_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Product'),
      '#default_value' => $product,
      '#target_type' => 'commerce_product',
      '#weight' => -10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['product_id'] = $values['product_id'];
  }

}
