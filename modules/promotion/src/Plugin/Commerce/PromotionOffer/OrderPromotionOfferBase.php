<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_order\PriceSplitterInterface;
use Drupal\commerce_price\RounderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base class for order offers.
 */
abstract class OrderPromotionOfferBase extends PromotionOfferBase implements OrderPromotionOfferInterface {

  /**
   * The price splitter.
   *
   * @var \Drupal\commerce_order\PriceSplitterInterface
   */
  protected $splitter;

  /**
   * Constructs a new OrderPromotionOfferBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The pluginId for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The rounder.
   * @param \Drupal\commerce_order\PriceSplitterInterface $splitter
   *   The splitter.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RounderInterface $rounder, PriceSplitterInterface $splitter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $rounder);

    $this->splitter = $splitter;
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
      $container->get('commerce_order.price_splitter')
    );
  }

}
