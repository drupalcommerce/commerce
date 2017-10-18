<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the product variation storage, in multilingual context.
 *
 * @group commerce
 */
class ProductVariationStorageMultilingualTest extends CommerceKernelTestBase {

  /**
   * The product variation storage.
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

  /**
   * The language default.
   *
   * @var \Drupal\Core\Language\LanguageDefault
   */
  protected $languageDefault;

  /**
   * The testing SKU.
   *
   * @var string
   */
  protected $testSku = 'STORAGE-MULTILINGUAL-TEST';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'commerce_product',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['commerce_product']);

    $this->variationStorage = $this->container->get('entity_type.manager')->getStorage('commerce_product_variation');
    $this->languageManager = $this->container->get('language_manager');
    $this->languageDefault = $this->container->get('language.default');

    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('sr')->save();
    ConfigurableLanguage::createFromLangcode('de')->save();

    $variation_type = ProductVariationType::load('default');
    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product_variation', $variation_type->id(), TRUE);

    $sku = 'STORAGE-MULTILINGUAL-TEST';
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => $sku,
      'title' => 'English variation',
    ]);
    $variation->addTranslation('fr', [
      'title' => 'Variation française',
    ]);
    $variation->addTranslation('sr', [
      'title' => 'Srpska varijacija',
    ]);
    $variation->save();
    $product = Product::create([
      'type' => 'default',
      'variations' => [$variation],
    ]);
    $product->addTranslation('fr');
    $product->addTranslation('sr');
    $product->save();
  }

  /**
   * Tests loading variations by SKU in French.
   */
  public function testLoadBySkuFr() {
    $this->languageDefault->set($this->languageManager->getLanguage('fr'));
    $result = $this->variationStorage->loadBySku($this->testSku);
    $this->assertEquals('Variation française', $result->label());
  }

  /**
   * Tests loading variations by SKU in Serbian.
   */
  public function testLoadBySkuSr() {
    $this->languageDefault->set($this->languageManager->getLanguage('sr'));
    $result = $this->variationStorage->loadBySku($this->testSku);
    $this->assertEquals('Srpska varijacija', $result->label());
  }

  /**
   * Tests loading variations by SKU in German (untranslated language.)
   */
  public function testLoadBySkuDe() {
    $this->languageDefault->set($this->languageManager->getLanguage('de'));
    $result = $this->variationStorage->loadBySku($this->testSku);
    // @todo how can we get this to fall back to `und`?
    $this->assertEquals(NULL, $result->label());
  }

  /**
   * Tests loadEnabled() method.
   */
  public function testLoadEnabled() {
    // @todo PORT AS MULTILINGUAL
  }

  /**
   * Tests loadFromContext() method.
   */
  public function testLoadFromContext() {
    // @todo PORT AS MULTILINGUAL
  }

}
