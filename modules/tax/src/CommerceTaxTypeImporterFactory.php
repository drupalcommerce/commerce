<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\CommerceTaxTypeImporterFactory.
 */

namespace Drupal\commerce_tax;

use \Drupal\Core\Entity\EntityManagerInterface;
use \Drupal\Core\StringTranslation\TranslationInterface;

class CommerceTaxTypeImporterFactory implements CommerceTaxTypeImporterFactoryInterface {

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
  public function __construct(EntityManagerInterface $entity_manager, TranslationInterface $string_translation) {
    $this->entityManager = $entity_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($tax_types_folder = NULL) {
    return new CommerceTaxTypeImporter($this->entityManager, $this->stringTranslation, $tax_types_folder);
  }

}
