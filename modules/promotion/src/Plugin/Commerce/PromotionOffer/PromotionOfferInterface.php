<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Executable\ExecutableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for Offer plugins.
 */
interface PromotionOfferInterface extends ConfigurablePluginInterface, ContainerFactoryPluginInterface, ContextAwarePluginInterface, ExecutableInterface, PluginFormInterface {

  /**
   * Gets the parent promotion.
   *
   * @return \Drupal\commerce_promotion\Entity\PromotionInterface
   *   The promotion.
   */
  public function getPromotion();

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder();

}
