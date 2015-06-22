<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\TaxTypeImporterFactory.
 */

namespace Drupal\commerce_tax;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

class TaxTypeImporterFactory implements TaxTypeImporterFactoryInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Constructs the factory.
   */
  public function __construct(EntityManagerInterface $entityManager, TranslationInterface $stringTranslation) {
    $this->entityManager = $entityManager;
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($taxTypesFolder = NULL) {
    return new TaxTypeImporter($this->entityManager, $this->stringTranslation, $taxTypesFolder);
  }

}
