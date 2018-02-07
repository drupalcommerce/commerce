<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for offers.
 */
interface PromotionOfferInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Gets the offer entity type ID.
   *
   * This is the entity type ID of the entity passed to apply().
   *
   * @return string
   *   The offer's entity type ID.
   */
  public function getEntityTypeId();

  /**
   * Applies the offer to the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface $promotion
   *   THe parent promotion.
   */
  public function apply(EntityInterface $entity, PromotionInterface $promotion);

}
