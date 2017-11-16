<?php


namespace Drupal\commerce_product;


use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

class ProductVariationRepository implements ProductVariationRepositoryInterface {

  /**
   * The variation storage.
   *
   * @var \Drupal\commerce_product\ProductVariationStorageInterface
   */
  protected $variationStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manage, LanguageManagerInterface $language_manager ) {
    $this->variationStorage = $entity_type_manage->getStorage('commerce_product_variation');
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function loadBySku($sku) {
    $variation = $this->variationStorage->loadBySku($sku);
    $translated = $this->ensureTranslations([$variation]);
    return reset($translated);
  }

  /**
   * {@inheritdoc}
   */
  public function loadFromContext(ProductInterface $product) {
    $variation = $this->variationStorage->loadFromContext($product);
    $translated = $this->ensureTranslations([$variation], $product->language()->getId());
    return reset($translated);
  }

  /**
   * {@inheritdoc}
   */
  public function loadEnabled(ProductInterface $product) {
    $langcode = $product->language()->getId();
    $variations = $this->variationStorage->loadEnabled($product);
    return $this->ensureTranslations($variations, $langcode);
  }

  /**
   * Ensures entities are in the current entity's language, if possible.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   The entities to process.
   * @param string $langcode
   *   (optional) The language of the current context. Defaults to the current
   *   content language.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   The processed entities.
   */
  protected function ensureTranslations(array $entities, $langcode = NULL) {
    if (empty($langcode)) {
      $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    }
    foreach ($entities as $index => $entity) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entities[$index] = ($entity->hasTranslation($langcode)) ? $entity->getTranslation($langcode) : $entity;
    }

    return $entities;
  }


}
