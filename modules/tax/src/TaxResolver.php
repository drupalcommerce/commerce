<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\TaxResolverFactory.
 */

namespace Drupal\commerce_tax;

use CommerceGuys\Tax\Resolver\TaxResolver as BaseTaxResolver;
use CommerceGuys\Tax\Resolver\Engine\TaxTypeResolverEngine;
use CommerceGuys\Tax\Resolver\Engine\TaxRateResolverEngine;
use Drupal\commerce_tax\TaxTypeRepository;
use Drupal\commerce_tax\Plugin\CommerceTax\TaxTypeResolver;

class TaxResolver extends BaseTaxResolver {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    // Tax Type Resolver.
    $taxTypeRepository = new TaxTypeRepository();
    $this->taxTypeResolverEngine = new TaxTypeResolverEngine();

    $tax_type_resolver_manager = \Drupal::service('plugin.manager.commerce_tax.tax_type_resolver');
    foreach ($tax_type_resolver_manager->getDefinitions() as $resolver) {
      $this->taxTypeResolverEngine->add(new $resolver['class']($taxTypeRepository));
    }

    // Tax Rate Resolver.
    $this->taxRateResolverEngine = new TaxRateResolverEngine();

    $tax_rate_resolver_manager = \Drupal::service('plugin.manager.commerce_tax.tax_type_resolver');
    foreach ($tax_rate_resolver_manager->getDefinitions() as $resolver) {
      $this->taxRateResolverEngine->add(new $resolver['class']());
    }

  }

}
