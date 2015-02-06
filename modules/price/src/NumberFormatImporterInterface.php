<?php

/**
 * @file
 * Contains \Drupal\commerce_price\NumberFormatImporterInterface.
 */

namespace Drupal\commerce_price;

use Drupal\Core\Language\LanguageInterface;

interface NumberFormatImporterInterface {

  /**
   * Import a number format.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language.
   *
   * @return \Drupal\commerce_price\Entity\NumberFormat|bool
   *    Returns the number_format entity or false if something went wrong.
   */
  public function importNumberFormat(LanguageInterface $language);

}
