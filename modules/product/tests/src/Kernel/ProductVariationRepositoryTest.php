<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductType;
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
class ProductVariationRepositoryTest extends CommerceKernelTestBase {

  /**
   * The product variation storage.
   *
   * @var \Drupal\commerce_product\ProductVariationRepositoryInterface
   */
  protected $variationRepository;

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
   * The test product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

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

    $this->variationRepository = $this->container->get('commerce_product.variation_repository');
    $this->languageManager = $this->container->get('language_manager');
    $this->languageDefault = $this->container->get('language.default');

    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('sr')->save();
    ConfigurableLanguage::createFromLangcode('de')->save();

    $variation_type = ProductVariationType::create([
      'id' => 'multilingual',
      'label' => 'Multilingual',
      'orderItemType' => 'default',
      'generateTitle' => FALSE,
    ]);
    $variation_type->save();
    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product_variation', $variation_type->id(), TRUE);
    $product_type = ProductType::create([
      'id' => 'multilingual',
      'label' => 'Multilingual',
      'variationType' => $variation_type->id(),
    ]);
    $product_type->save();
    commerce_product_add_stores_field($product_type);
    commerce_product_add_variations_field($product_type);
    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product', $product_type->id(), TRUE);

    $sku = 'STORAGE-MULTILINGUAL-TEST';
    $variation = ProductVariation::create([
      'type' => $variation_type->id(),
      'sku' => $sku,
      'title' => 'English variation',
    ]);
    $variation->save();
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $this->reloadEntity($variation);
    $variation->addTranslation('fr', [
      'title' => 'Variation française',
    ]);
    $variation->addTranslation('sr', [
      'title' => 'Srpska varijacija',
    ]);
    $variation->save();
    $product = Product::create([
      'type' => $product_type->id(),
      'variations' => [$variation],
    ]);
    $product->addTranslation('fr');
    $product->addTranslation('sr');
    $product->save();
    $this->product = $product;
  }

  /**
   * Tests loading variations by SKU in French.
   */
  public function testLoadBySkuFr() {
    $this->languageDefault->set($this->languageManager->getLanguage('fr'));
    $result = $this->variationRepository->loadBySku($this->testSku);
    $this->assertEquals('Variation française', $result->label());
  }

  /**
   * Tests loading variations by SKU in Serbian.
   */
  public function testLoadBySkuSr() {
    $this->languageDefault->set($this->languageManager->getLanguage('sr'));
    $result = $this->variationRepository->loadBySku($this->testSku);
    $this->assertEquals('Srpska varijacija', $result->label());
  }

  /**
   * Tests loading variations by SKU in German (untranslated language.)
   */
  public function testLoadBySkuDe() {
    $this->languageDefault->set($this->languageManager->getLanguage('de'));
    $result = $this->variationRepository->loadBySku($this->testSku);
    $this->assertEquals('English variation', $result->label());
  }

  /**
   * Tests loadEnabled() method.
   */
  public function testLoadEnabled() {
    $enabled = $this->variationRepository->loadEnabled($this->product);
    $enabled_variation = reset($enabled);
    $this->assertEquals($enabled_variation->language()->getId(), 'en');

    $enabled = $this->variationRepository->loadEnabled($this->product->getTranslation('fr'));
    $enabled_variation = reset($enabled);
    $this->assertEquals($enabled_variation->language()->getId(), 'fr');
  }

  /**
   * Tests loadFromContext() method.
   */
  public function testLoadFromContext() {
    $product = $this->product->getTranslation('sr');
    $request = Request::create('');
    $request->query->add([
      'v' => $product->getDefaultVariation()->id(),
    ]);
    // Push the request to the request stack so `current_route_match` works.
    $this->container->get('request_stack')->push($request);
    $context_variation = $this->variationRepository->loadFromContext($product);
    $this->assertEquals($context_variation->language()->getId(), 'sr');
  }

}
