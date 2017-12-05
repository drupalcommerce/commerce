<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxType;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the base interface for tax types.
 *
 * Tax types can be local and remote, therefore each tax type
 * plugin actually implements one of the child interfaces.
 *
 * @see \Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeInterface
 * @see \Drupal\commerce_tax\Plugin\Commerce\TaxType\RemoteTaxTypeInterface
 */
interface TaxTypeInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Gets the tax type label.
   *
   * @return string
   *   The tax type label.
   */
  public function getLabel();

  /**
   * Gets whether the tax type is display inclusive.
   *
   * E.g. US sales tax is not display inclusive, a $5 price is shown as $5
   * even if a $1 tax has been calculated. In France, a 5€ price is shown as
   * 6€ if a 1€ tax was calculated, because French VAT is display inclusive.
   *
   * @return bool
   *   TRUE if the tax type is display inclusive, FALSE otherwise.
   */
  public function isDisplayInclusive();

  /**
   * Checks whether the tax type applies to the given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return bool
   *   TRUE if the tax type applies, FALSE otherwise.
   */
  public function applies(OrderInterface $order);

  /**
   * Applies the tax type to the given order.
   *
   * Taxes should be added on the order item level, to make returns
   * and refunds easier. This is true even for taxes that are only
   * shown at the order level, such as sales taxes.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function apply(OrderInterface $order);

}
