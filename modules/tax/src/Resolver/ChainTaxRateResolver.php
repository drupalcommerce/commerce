<?php

namespace Drupal\commerce_tax\Resolver;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_tax\TaxZone;
use Drupal\profile\Entity\ProfileInterface;

class ChainTaxRateResolver implements ChainTaxRateResolverInterface {

  use TaxTypeAwareTrait;

  /**
   * The resolvers.
   *
   * @var \Drupal\commerce_tax\Resolver\TaxRateResolverInterface[]
   */
  protected $resolvers = [];

  /**
   * Constructs a new ChainTaxRateResolver object.
   *
   * @param \Drupal\commerce_tax\Resolver\TaxRateResolverInterface[] $resolvers
   *   The resolvers.
   */
  public function __construct(array $resolvers = []) {
    $this->resolvers = $resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function addResolver(TaxRateResolverInterface $resolver) {
    $this->resolvers[] = $resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getResolvers() {
    return $this->resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(TaxZone $zone, OrderItemInterface $order_item, ProfileInterface $customer_profile) {
    $result = NULL;
    foreach ($this->resolvers as $resolver) {
      if ($resolver instanceof TaxTypeAwareInterface) {
        $resolver->setTaxType($this->taxType);
      }
      $result = $resolver->resolve($zone, $order_item, $customer_profile);
      if ($result) {
        break;
      }
    }

    return $result;
  }

}
