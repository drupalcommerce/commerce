<?php

/**
 * @file
 * Contains \Drupal\commerce_tax\CommerceTaxTypeImporter.
 */

namespace Drupal\commerce_tax;

use \CommerceGuys\Tax\Repository\TaxTypeRepository;
use \Drupal\Core\Entity\EntityManagerInterface;
use \Drupal\Core\Entity\EntityStorageInterface;
use \CommerceGuys\Tax\Model\TaxTypeInterface;
use \CommerceGuys\Tax\Model\TaxRateInterface;
use \CommerceGuys\Tax\Model\TaxRateAmountInterface;
use \Drupal\Core\StringTranslation\StringTranslationTrait;
use \Drupal\Core\StringTranslation\TranslationInterface;

class CommerceTaxTypeImporter implements CommerceTaxTypeImporterInterface {

  use StringTranslationTrait;

  /**
   * The tax type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $taxTypeStorage;

  /**
   * The tax rate storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $taxRateStorage;

  /**
   * The tax rate amount storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $taxRateAmountStorage;

  /**
   * The tax type repository.
   *
   * @var \CommerceGuys\Tax\Repository\TaxTypeRepositoryInterface
   */
  protected $taxTypeRepository;

  /**
   * Constructs a new CommerceTaxTypeImporter.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param string
   *   The tax types folder of definitions.
   */
  public function __construct(EntityManagerInterface $entity_manager, TranslationInterface $string_translation, $tax_types_folder = NULL) {
    $this->taxTypeStorage = $entity_manager->getStorage('commerce_tax_type');
    $this->taxRateStorage = $entity_manager->getStorage('commerce_tax_rate');
    $this->taxRateAmountStorage = $entity_manager->getStorage('commerce_tax_rate_amount');
    $this->stringTranslation = $string_translation;
    $this->taxTypeRepository = new TaxTypeRepository($tax_types_folder);
  }

  /**
   * {@inheritdoc}
   */
  public function getImportableTaxTypes() {
    $importable_tax_types = $this->taxTypeRepository->getAll();
    $imported_tax_types = $this->taxTypeStorage->loadMultiple();

    // Remove any already imported tax types.
    foreach ($imported_tax_types as $tax_type) {
      if (isset($importable_tax_types[$tax_type->getId()])) {
        unset($importable_tax_types[$tax_type->getId()]);
      }
    }

    return $importable_tax_types;
  }

  /**
   * {@inheritdoc}
   */
  public function createTaxType($tax_type_id) {
    $tax_type = $this->taxTypeRepository->get($tax_type_id);
    return $this->importTaxType($tax_type);
  }

  /**
   * Imports a single tax type.
   *
   * @param \CommerceGuys\Tax\Model\TaxTypeInterface $tax_type
   *   The tax type.
   */
  protected function importTaxType(TaxTypeInterface $tax_type) {
    if ($this->taxTypeStorage->load($tax_type->getId())) {
      return;
    }

    $values = array(
      'id' => $tax_type->getId(),
      'name' => $this->t($tax_type->getName()),
      'compound' => $tax_type->isCompound(),
      'roundingMode' => $tax_type->getRoundingMode(),
      'tag' => $tax_type->getTag(),
      'rates' => array_keys($tax_type->getRates()),
    );

    return $this->taxTypeStorage->create($values);
  }

  /**
   * Imports a single tax rate.
   *
   * @param \CommerceGuys\Tax\Model\TaxRateInterface $tax_rate
   *   The tax rate to import.
   */
  public function importTaxRate(TaxRateInterface $tax_rate) {
    $values = array(
      'type' => $tax_rate->getType()->getId(),
      'id' => $tax_rate->getId(),
      'name' => $this->t($tax_rate->getName()),
      'displayName' => $this->t($tax_rate->getDisplayName()),
      'default' => $tax_rate->isDefault(),
      'amounts' => array_keys($tax_rate->getAmounts()),
    );

    return $this->taxRateStorage->create($values);
  }

  /**
   * Imports a single tax rate amount.
   *
   * @param \CommerceGuys\Tax\Model\TaxRateAmountInterface $tax_rate_amount
   *   The tax rate amount to import.
   */
  protected function importTaxRateAmount(TaxRateAmountInterface $tax_rate_amount) {
    $start_date = $tax_rate_amount->getStartDate() ? $tax_rate_amount->getStartDate()->getTimestamp() : NULL;
    $end_date = $tax_rate_amount->getEndDate() ? $tax_rate_amount->getEndDate()->getTimestamp() : NULL;
    $values = array(
      'rate' => $tax_rate_amount->getRate()->getId(),
      'id' => $tax_rate_amount->getId(),
      'amount' => $tax_rate_amount->getAmount(),
      'startDate' => $start_date,
      'endDate' => $end_date,
    );

    return $this->taxRateAmountStorage->create($values);
  }

}
