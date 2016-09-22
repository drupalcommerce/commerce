<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_order\EntityAdjustableInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Executable\ExecutableInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for Offer plugins.
 */
interface PromotionOfferInterface extends ExecutableInterface, PluginFormInterface, ConfigurablePluginInterface, PluginInspectionInterface, ContextAwarePluginInterface {

  const ORDER = 'commerce_order';
  const ORDER_ITEM = 'commerce_order_item';

  /**
   * Gets the entity type the offer is for.
   *
   * @return string
   *   The entity type it applies to.
   */
  public function getTargetEntityType();

  /**
   * Get the target entity for the offer.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The target entity.
   */
  public function getTargetEntity();

  /**
   * Applies the promotion offer's adjustment to an item.
   *
   * @param \Drupal\commerce_order\EntityAdjustableInterface $entity
   *   The adjustable entity.
   * @param \Drupal\commerce_price\Price $amount
   *   The price object.
   *
   * @return \Drupal\commerce_order\Adjustment
   *   The adjustment.
   */
  public function applyAdjustment(EntityAdjustableInterface $entity, Price $amount);

}
