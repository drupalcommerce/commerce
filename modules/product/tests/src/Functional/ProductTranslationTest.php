<?php

namespace Drupal\Tests\commerce_product\Functional;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;

/**
 * Tests translating products and variations.
 *
 * @group commerce
 */
class ProductTranslationTest extends ProductBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'config_translation',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_product_attribute',
      'administer languages',
      'administer content translation',
      'translate any entity',
      'translate configuration',
      'access content overview',
      'create content translations',
      'update content translations',
      'delete content translations',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add the French and German languages.
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm(['predefined_langcode' => 'fr'], t('Add language'));
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm(['predefined_langcode' => 'de'], t('Add language'));

    // Enable content translation on products and variations.
    $this->drupalGet('admin/config/regional/content-language');
    $edit = [
      'entity_types[commerce_product]' => TRUE,
      'settings[commerce_product][default][translatable]' => TRUE,
      'entity_types[commerce_product_variation]' => TRUE,
      'settings[commerce_product_variation][default][translatable]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    // Adding languages requires a container rebuild in the test running
    // environment so that multilingual services are used.
    $this->resetAll();
  }

  /**
   * Test translating a product and its variations.
   */
  public function testProductTranslation() {
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'Translation test product',
      'stores' => $this->stores,
    ]);
    $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'product_id' => $product->id(),
      'sku' => $this->randomMachineName(),
      'price' => new Price('9.99', 'USD'),
    ]);
    $this->drupalGet($product->toUrl('edit-form'));
    $this->getSession()->getPage()->clickLink('Translate');
    $this->assertSession()->linkByHrefExists("/product/{$product->id()}/translations/add/en/fr");
    $this->getSession()->getPage()->clickLink('Add');
    $this->getSession()->getPage()->fillField('Title', 'Produit de test de traduction');
    $this->getSession()->getPage()->pressButton('Save (this translation)');
    $this->assertSession()->pageTextContains('The product Produit de test de traduction has been successfully saved.');

    $this->drupalGet(Url::fromRoute('entity.commerce_product_variation.collection', [
      'commerce_product' => $product->id(),
    ]));
    $this->assertSession()->linkByHrefExists('/product/1/variations/1/translations');
    $this->getSession()->getPage()->clickLink('Translate');
    $this->assertSession()->linkByHrefExists('/fr/product/1/variations/1/translations/add/en/fr');
    $this->getSession()->getPage()->clickLink('Add');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('Saved the Produit de test de traduction variation.');
  }

  /**
   * Test translating a single-variation product.
   */
  public function testSingleVariationProductTranslation() {
    $this->drupalGet('admin/commerce/config/product-types/default/edit');
    $edit = [
      'multipleVariations' => FALSE,
      'language_configuration[language_alterable]' => TRUE,
    ];
    $this->submitForm($edit, t('Save'));

    $this->drupalGet('admin/commerce/config/product-variation-types/default/edit');
    $edit = [
      'generateTitle' => FALSE,
    ];
    $this->submitForm($edit, t('Save'));

    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'Translation test product',
      'stores' => $this->stores,
    ]);
    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'product_id' => $product->id(),
      'title' => 'Hat',
      'sku' => $this->randomMachineName(),
      'price' => new Price('9.99', 'USD'),
    ]);
    $this->drupalGet($product->toUrl('edit-form'));
    $this->getSession()->getPage()->clickLink('Translate');
    $this->assertSession()->linkByHrefExists("/product/{$product->id()}/translations/add/en/fr");
    $this->getSession()->getPage()->clickLink('Add');
    $this->getSession()->getPage()->fillField('title[0][value]', 'Produit de test de traduction');
    $this->getSession()->getPage()->fillField('variations[entity][title][0][value]', 'Le Chapeau');
    $this->getSession()->getPage()->pressButton('Save (this translation)');
    $this->assertSession()->pageTextContains('The product Produit de test de traduction has been successfully saved.');

    // Confirm that the variation was translated together with the product.
    \Drupal::entityTypeManager()->getStorage('commerce_product_variation')->resetCache([1]);
    $variation = ProductVariation::load(1);
    $this->assertEquals('en', $variation->language()->getId());
    $this->assertEquals('Hat', $variation->getTitle());
    $this->assertTrue($variation->hasTranslation('fr'));
    $translation = $variation->getTranslation('fr');
    $this->assertEquals('Le Chapeau', $translation->getTitle());

    // Edit the product and change the language to German.
    $this->drupalGet($product->toUrl('edit-form', ['language' => new Language(['id' => 'en'])]));
    $this->submitForm(['langcode[0][value]' => 'de'], 'Save');
    $this->assertSession()->pageTextContains('The product Translation test product has been successfully saved.');

    \Drupal::entityTypeManager()->getStorage('commerce_product')->resetCache([1]);
    $product = Product::load(1);
    $this->assertEquals('de', $product->language()->getId());
    $this->assertTrue($product->hasTranslation('fr'));

    // Confirm that the variation language was changed as well.
    \Drupal::entityTypeManager()->getStorage('commerce_product_variation')->resetCache([1]);
    $variation = ProductVariation::load(1);
    $this->assertEquals('de', $variation->language()->getId());
    $this->assertEquals('Hat', $variation->getTitle());
    $this->assertTrue($variation->hasTranslation('fr'));
  }

}
