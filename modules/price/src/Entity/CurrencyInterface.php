<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Entity\CurrencyInterface.
 */

namespace Drupal\commerce_price\Entity;

use CommerceGuys\Intl\Currency\CurrencyEntityInterface as ExternalCurrencyInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for currencies.
 */
interface CurrencyInterface extends ExternalCurrencyInterface, ConfigEntityInterface {
}
