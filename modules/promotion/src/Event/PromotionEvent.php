<?php

namespace Drupal\commerce_promotion\Event;

use Drupal\commerce_promotion\Entity\PromotionInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the promotion event.
 *
 * @see \Drupal\commerce_promotion\Event\PromotionEvents
 */
class PromotionEvent extends Event {

  /**
   * The promotion.
   *
   * @var \Drupal\commerce_promotion\Entity\PromotionInterface
   */
  protected $promotion;

  /**
   * Constructs a new PromotionEvent.
   *
   * @param \Drupal\commerce_promotion\Entity\PromotionInterface $promotion
   *   The promotion.
   */
  public function __construct(PromotionInterface $promotion) {
    $this->promotion = $promotion;
  }

  /**
   * Gets the promotion.
   *
   * @return \Drupal\commerce_promotion\Entity\PromotionInterface
   *   The promotion.
   */
  public function getPromotion() {
    return $this->promotion;
  }

}
