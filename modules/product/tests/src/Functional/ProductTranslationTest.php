<?php

namespace Drupal\Tests\commerce_product\Functional;

use Drupal\commerce_price\Price;
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

    // Add the French language.
    $edit = ['predefined_langcode' => 'fr'];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, t('Add language'));

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

}
