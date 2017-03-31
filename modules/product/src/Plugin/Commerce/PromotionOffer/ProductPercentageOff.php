<?php

namespace Drupal\commerce_product\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\PercentageOffBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Order item: Percentage off' condition.
 *
 * @CommercePromotionOffer(
 *   id = "commerce_promotion_product_percentage_off",
 *   label = @Translation("Percentage off product in the order"),
 * )
 */
class ProductPercentageOff extends PercentageOffBase {

  protected $productStorage;

  /**
   * Constructs a new ProductEquals object.
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
    if ($product_id) {
      foreach ($this->getOrder()->getItems() as $order_item) {
        /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variant */
        $variant = $order_item->getPurchasedEntity();
        if ($variant && $variant->getProductId() == $product_id) {
          $adjustment_amount = $order_item->getUnitPrice()->multiply($this->getAmount());
          $adjustment_amount = $this->rounder->round($adjustment_amount);
          $this->applyAdjustment($order_item, $adjustment_amount);
          break;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $product = $this->productStorage->load($this->configuration['product_id']);
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
    $values = $form_state->getValue($form['#parents']);
    $this->configuration['product_id'] = $values['product_id'];
    parent::submitConfigurationForm($form, $form_state);
  }

}
