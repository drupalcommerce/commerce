<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\TaxTypeImporterFactory.
 */

namespace Drupal\commerce_tax;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

class TaxTypeImporterFactory implements TaxTypeImporterFactoryInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Constructs the factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, TranslationInterface $stringTranslation) {
    $this->entityTypeManager = $entityTypeManager;
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($taxTypesFolder = NULL) {
    return new TaxTypeImporter($this->entityTypeManager, $this->stringTranslation, $taxTypesFolder);
  }

}
