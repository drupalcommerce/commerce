<?php

namespace Drupal\Tests\commerce_product\Functional;

use Drupal\commerce\EntityHelper;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;

/**
 * Create, view, edit, delete, and change products.
 *
 * @group commerce
 */
class ProductAdminTest extends ProductBrowserTestBase {

  /**
   * Tests creating a product.
   */
  public function testCreateProduct() {
    $this->drupalGet('admin/commerce/products');
    $this->getSession()->getPage()->clickLink('Add product');

    $store_ids = EntityHelper::extractIds($this->stores);
    $title = $this->randomMachineName();
    $edit = [
      'title[0][value]' => $title,
    ];
    foreach ($store_ids as $store_id) {
      $edit['stores[target_id][value][' . $store_id . ']'] = $store_id;
    }
    $this->submitForm($edit, 'Save');

    $result = \Drupal::entityQuery('commerce_product')
      ->condition("title", $edit['title[0][value]'])
      ->range(0, 1)
      ->execute();
    $product_id = reset($result);
    $product = Product::load($product_id);

    $this->assertNotNull($product, 'The new product has been created.');
    $this->assertSession()->pageTextContains(t('The product @title has been successfully saved', ['@title' => $title]));
    $this->assertSession()->pageTextContains($title);
    $this->assertFieldValues($product->getStores(), $this->stores, 'Created product has the correct associated stores.');
    $this->assertFieldValues($product->getStoreIds(), $store_ids, 'Created product has the correct associated store ids.');
    $this->drupalGet($product->toUrl('canonical'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($product->getTitle());
  }

  /**
   * Tests editing a product.
   */
  public function testEditProduct() {
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
    ]);

    // Check the integrity of the edit form.
    $this->drupalGet($product->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('title[0][value]');

    $title = $this->randomMachineName();
    $store_ids = EntityHelper::extractIds($this->stores);
    $edit = [
      'title[0][value]' => $title,
    ];
    foreach ($store_ids as $store_id) {
      $edit['stores[target_id][value][' . $store_id . ']'] = $store_id;
    }
    $this->submitForm($edit, 'Save');

    $this->container->get('entity_type.manager')->getStorage('commerce_product')->resetCache([$product->id()]);
    $product = Product::load($product->id());
    $this->assertEquals($product->getTitle(), $title, 'The product title successfully updated.');
    $this->assertFieldValues($product->getStores(), $this->stores, 'Updated product has the correct associated stores.');
    $this->assertFieldValues($product->getStoreIds(), $store_ids, 'Updated product has the correct associated store ids.');
  }

  /**
   * Tests deleting a product.
   */
  public function testDeleteProduct() {
    $product = $this->createEntity('commerce_product', [
      'title' => $this->randomMachineName(),
      'type' => 'default',
    ]);
    $this->drupalGet($product->toUrl('delete-form'));
    $this->assertSession()->pageTextContains(t("Are you sure you want to delete the product @product?", ['@product' => $product->getTitle()]));
    $this->assertSession()->pageTextContains(t('This action cannot be undone.'));
    $this->submitForm([], 'Delete');

    $this->container->get('entity_type.manager')->getStorage('commerce_product')->resetCache();
    $product_exists = (bool) Product::load($product->id());
    $this->assertEmpty($product_exists, 'The new product has been deleted from the database.');
  }

  /**
   * Tests viewing the admin/commerce/products page.
   */
  public function testAdminProducts() {
    $this->drupalGet('admin/commerce/products');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('You are not authorized to access this page.');
    $this->assertNotEmpty($this->getSession()->getPage()->hasLink('Add product'));

    // Create a default type product.
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'First product',
      'status' => TRUE,
    ]);
    // Create a second product type and products for that type.
    $values = [
      'id' => 'random',
      'label' => 'Random',
      'description' => 'My random product type',
      'variationType' => 'default',
    ];
    $product_type = $this->createEntity('commerce_product_type', $values);
    commerce_product_add_stores_field($product_type);
    commerce_product_add_variations_field($product_type);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $second_product */
    $second_product = $this->createEntity('commerce_product', [
      'type' => 'random',
      'title' => 'Second product',
      'status' => FALSE,
    ]);
    /** @var \Drupal\commerce_product\Entity\ProductInterface $third_product */
    $third_product = $this->createEntity('commerce_product', [
      'type' => 'random',
      'title' => 'Third product',
      'status' => TRUE,
    ]);

    $this->drupalGet('admin/commerce/products');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('You are not authorized to access this page.');
    $row_count = $this->getSession()->getPage()->findAll('xpath', '//table/tbody/tr');
    $this->assertEquals(3, count($row_count), 'Table has 3 rows.');

    // Confirm that product titles are displayed.
    $page = $this->getSession()->getPage();
    $product_count = $page->findAll('xpath', '//table/tbody/tr/td/a[text()="First product"]');
    $this->assertEquals(1, count($product_count), 'First product is displayed.');
    $product_count = $page->findAll('xpath', '//table/tbody/tr/td/a[text()="Second product"]');
    $this->assertEquals(1, count($product_count), 'Second product is displayed.');
    $product_count = $page->findAll('xpath', '//table/tbody/tr/td/a[text()="Third product"]');
    $this->assertEquals(1, count($product_count), 'Third product is displayed.');

    // Confirm that product types are displayed.
    $product_count = $page->findAll('xpath', '//table/tbody/tr/td[starts-with(text(), "Default")]');
    $this->assertEquals(1, count($product_count), 'Default product type exists in the table.');
    $product_count = $page->findAll('xpath', '//table/tbody/tr/td[starts-with(text(), "Random")]');
    $this->assertEquals(2, count($product_count), 'Random product types exist in the table.');

    // Confirm that product statuses are displayed.
    $product_count = $page->findAll('xpath', '//table/tbody/tr/td[starts-with(text(), "Unpublished")]');
    $this->assertEquals(1, count($product_count), 'Unpublished product exists in the table.');
    $product_count = $page->findAll('xpath', '//table/tbody/tr/td[starts-with(text(), "Published")]');
    $this->assertEquals(2, count($product_count), 'Published products exist in the table.');

    // Logout and check that anonymous users cannot see the products page
    // and receive a 403 error code.
    $this->drupalLogout();
    $this->drupalGet('admin/commerce/products');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->pageTextContains('You are not authorized to access this page.');
    $this->assertNotEmpty(!$this->getSession()->getPage()->hasLink('Add product'));

    // Login and confirm access for 'access commerce_product overview'
    // permission. The second product should no longer be visible because
    // it is unpublished.
    $user = $this->drupalCreateUser(['access commerce_product overview']);
    $this->drupalLogin($user);
    $this->drupalGet('admin/commerce/products');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('You are not authorized to access this page.');
    $this->assertNotEmpty(!$this->getSession()->getPage()->hasLink('Add product'));
    $row_count = $this->getSession()->getPage()->findAll('xpath', '//table/tbody/tr');
    $this->assertEquals(2, count($row_count), 'Table has 3 rows.');

    // Confirm that product titles are displayed.
    $page = $this->getSession()->getPage();
    $product_count = $page->findAll('xpath', '//table/tbody/tr/td/a[text()="First product"]');
    $this->assertEquals(1, count($product_count), 'First product is displayed.');
    $product_count = $page->findAll('xpath', '//table/tbody/tr/td/a[text()="Third product"]');
    $this->assertEquals(1, count($product_count), 'Third product is displayed.');

    // Confirm that product types are displayed.
    $product_count = $page->findAll('xpath', '//table/tbody/tr/td[starts-with(text(), "Default")]');
    $this->assertEquals(1, count($product_count), 'Default product type exists in the table.');
    $product_count = $page->findAll('xpath', '//table/tbody/tr/td[starts-with(text(), "Random")]');
    $this->assertEquals(1, count($product_count), 'Random product type exist in the table.');

    // Confirm that the right product statuses are displayed.
    $product_count = $page->findAll('xpath', '//table/tbody/tr/td[starts-with(text(), "Unpublished")]');
    $this->assertEquals(0, count($product_count), 'Unpublished product do not exist in the table.');
    $product_count = $page->findAll('xpath', '//table/tbody/tr/td[starts-with(text(), "Published")]');
    $this->assertEquals(2, count($product_count), 'Published products exist in the table.');

    // Login and confirm access for "view own unpublished commerce_product".
    $user = $this->drupalCreateUser([
      'access commerce_product overview',
      'view own unpublished commerce_product',
    ]);
    $second_product->setOwnerId($user->id());
    $second_product->save();
    $this->drupalLogin($user);
    $this->drupalGet('admin/commerce/products');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('You are not authorized to access this page.');
    $product_count = $page->findAll('xpath', '//table/tbody/tr/td/a[text()="Second product"]');
    $this->assertEquals(1, count($product_count), 'Second product is displayed.');
  }

  /**
   * Tests creating a product and its variations.
   */
  public function testVariationsTab() {
    $this->drupalGet('admin/commerce/products');
    $this->getSession()->getPage()->clickLink('Add product');

    // Create a product.
    $store_ids = EntityHelper::extractIds($this->stores);
    $title = $this->randomMachineName();
    $edit = [
      'title[0][value]' => $title,
    ];
    foreach ($store_ids as $store_id) {
      $edit['stores[target_id][value][' . $store_id . ']'] = $store_id;
    }
    $this->submitForm($edit, 'Save and add variations');

    $this->assertSession()->pageTextContains(t('The product @title has been successfully saved', ['@title' => $title]));
    $this->assertSession()->pageTextContains(t('There are no product variations yet.'));
    $this->assertNotEmpty($this->getSession()->getPage()->hasLink('Add variation'));

    // Create a variation.
    $this->getSession()->getPage()->clickLink('Add variation');
    $this->assertSession()->pageTextContains(t('Add variation'));
    $this->assertSession()->fieldExists('sku[0][value]');
    $this->assertSession()->fieldExists('price[0][number]');
    $this->assertSession()->fieldExists('status[value]');
    $this->assertSession()->buttonExists('Save');

    $variation_sku = $this->randomMachineName();
    $this->getSession()->getPage()->fillField('sku[0][value]', $variation_sku);
    $this->getSession()->getPage()->fillField('price[0][number]', '9.99');
    $this->submitForm([], t('Save'));
    $this->assertSession()->pageTextContains("Saved the $title variation.");
    $variation_in_table = $this->getSession()->getPage()->find('xpath', '//table/tbody/tr/td[text()="' . $variation_sku . '"]');
    $this->assertNotEmpty($variation_in_table);

    $product = Product::load(1);
    $variation = ProductVariation::load(1);
    $this->assertEquals($product->id(), $variation->getProductId());
    $this->assertEquals($variation_sku, $variation->getSku());

    $this->container->get('entity_type.manager')->getStorage('commerce_product')->resetCache([$product->id()]);
    $product = Product::load($product->id());
    $this->assertTrue($product->hasVariation($variation));
  }

  /**
   * Tests editing a product variation.
   */
  public function testEditVariation() {
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
    ]);
    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'product_id' => $product->id(),
      'sku' => strtolower($this->randomMachineName()),
    ]);

    // Check the integrity of the variation form.
    $this->drupalGet($variation->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('sku[0][value]');
    $this->assertSession()->fieldExists('price[0][number]');
    $this->assertSession()->buttonExists('Save');

    $new_sku = strtolower($this->randomMachineName());
    $new_price_amount = '1.11';
    $variations_edit = [
      'sku[0][value]' => $new_sku,
      'price[0][number]' => $new_price_amount,
      'status[value]' => 1,
    ];
    $this->submitForm($variations_edit, 'Save');
    $this->assertSession()->addressEquals($variation->toUrl('collection'));

    $this->container->get('entity_type.manager')->getStorage('commerce_product_variation')->resetCache([$variation->id()]);
    $variation = ProductVariation::load($variation->id());
    $this->assertEquals($variation->getSku(), $new_sku);
    $this->assertEquals($variation->getPrice()->getNumber(), $new_price_amount);
  }

  /**
   * Tests duplicating a product variation.
   */
  public function testDuplicateVariation() {
    $sku = strtolower($this->randomMachineName());
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
    ]);
    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'product_id' => $product->id(),
      'sku' => $sku,
      'price' => [
        'number' => '12.00',
        'currency_code' => 'USD',
      ],
      'status' => TRUE,
    ]);

    // Check the integrity of the variation form.
    $this->drupalGet($variation->toUrl('duplicate-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('sku[0][value]');
    $this->assertSession()->fieldExists('price[0][number]');
    $this->assertSession()->buttonExists('Save');

    // Confirm that we can't save the duplicate form with the existing SKU.
    $this->submitForm([], 'Save');
    $this->assertSession()->addressEquals($variation->toUrl('duplicate-form'));
    $this->assertSession()->pageTextContains(sprintf('The SKU "%s" is already in use and must be unique.', $sku));

    $new_sku = strtolower($this->randomMachineName());
    $variations_edit = [
      'sku[0][value]' => $new_sku,
    ];
    $this->submitForm($variations_edit, 'Save');
    $this->assertSession()->addressEquals($variation->toUrl('collection'));

    $expected_variation_id = $variation->id() + 1;
    $variation = ProductVariation::load($expected_variation_id);
    $this->assertEquals($variation->getSku(), $new_sku);
    $this->assertEquals($variation->getPrice()->getNumber(), '12.00');
    $this->assertTrue($variation->isPublished());
  }

  /**
   * Tests deleting a product variation.
   */
  public function testDeleteVariation() {
    $product = $this->createEntity('commerce_product', [
      'title' => $this->randomMachineName(),
      'type' => 'default',
    ]);
    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'product_id' => $product->id(),
      'sku' => strtolower($this->randomMachineName()),
    ]);

    $this->drupalGet($variation->toUrl('delete-form'));
    $this->assertSession()->pageTextContains(t("Are you sure you want to delete the @variation variation?", [
      '@variation' => $variation->label(),
    ]));
    $this->assertSession()->pageTextContains(t('This action cannot be undone.'));
    $this->submitForm([], 'Delete');
    $this->assertSession()->addressEquals($variation->toUrl('collection'));

    $this->container->get('entity_type.manager')->getStorage('commerce_product_variation')->resetCache();
    $variation_exists = (bool) ProductVariation::load($variation->id());
    $this->assertEmpty($variation_exists, 'The new variation has been deleted from the database.');
  }

  /**
   * Tests the single variation mode.
   */
  public function testSingleVariationMode() {
    $this->drupalGet('admin/commerce/config/product-types/default/edit');
    $edit = [
      'multipleVariations' => FALSE,
    ];
    $this->submitForm($edit, t('Save'));

    $this->drupalGet('admin/commerce/products');
    $this->getSession()->getPage()->clickLink('Add product');
    $this->assertSession()->buttonNotExists('Save and add variations');
    $this->assertSession()->fieldExists('variations[entity][sku][0][value]');

    $store_id = $this->stores[0]->id();
    $title = $this->randomMachineName();
    $sku = strtolower($this->randomMachineName());
    $edit = [
      'title[0][value]' => $title,
      'stores[target_id][value][' . $store_id . ']' => $store_id,
      'variations[entity][sku][0][value]' => $sku,
      'variations[entity][price][0][number]' => '99.99',
    ];
    $this->submitForm($edit, 'Save');

    $product = Product::load(1);
    $this->assertNotEmpty($product);
    $this->assertEquals($title, $product->getTitle());
    $this->assertEquals([$store_id], $product->getStoreIds());
    $variation = $product->getDefaultVariation();
    $this->assertNotEmpty($variation);
    $this->assertEquals($sku, $variation->getSku());
    $this->assertEquals(new Price('99.99', 'USD'), $variation->getPrice());

    $this->drupalGet($product->toUrl('edit-form'));
    $edit = [
      'title[0][value]' => 'New title',
      'variations[entity][price][0][number]' => '199.99',
    ];
    $this->submitForm($edit, 'Save');

    \Drupal::entityTypeManager()->getStorage('commerce_product')->resetCache([1]);
    \Drupal::entityTypeManager()->getStorage('commerce_product_variation')->resetCache([1]);
    $product = Product::load(1);
    $this->assertNotEmpty($product);
    $this->assertEquals('New title', $product->getTitle());
    $this->assertEquals([$store_id], $product->getStoreIds());
    $variation = $product->getDefaultVariation();
    $this->assertNotEmpty($variation);
    $this->assertEquals(1, $variation->id());
    $this->assertEquals($sku, $variation->getSku());
    $this->assertEquals(new Price('199.99', 'USD'), $variation->getPrice());

    // The variation collection page should be inaccessible.
    $this->drupalGet($variation->toUrl('collection'));
    $this->assertSession()->statusCodeEquals('403');
  }

}
