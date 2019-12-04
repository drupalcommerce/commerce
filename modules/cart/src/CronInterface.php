<?php

namespace Drupal\commerce_cart;

/**
 * Provides the interface for the cart cron.
 */
interface CronInterface {

  /**
   * Runs the cron.
   */
  public function run();

}
