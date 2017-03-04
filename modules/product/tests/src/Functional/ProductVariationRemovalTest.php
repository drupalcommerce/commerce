<?php

namespace Drupal\Tests\commerce_product\Functional;

use Drupal\commerce_product\Entity\Product;

/**
 * Test "Reference to variations is not removed when the variation is removed".
 *
 * @link https://www.drupal.org/node/2792885
 *
 * @group commerce
 */
class ProductVariationRemovalTest extends ProductBrowserTestBase {

  /**
   * Tests #2792885.
   */
  public function testProductVariationRemoval() {
    $variations = [];
    $variation[] = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
    ]);
    $variation[] = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
    ]);
    $variation[] = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
    ]);
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'variations' => $variations,
    ]);

    $this->drupalGet($product->toUrl('edit-form'));
    $this->getSession()->getPage()->pressButton('edit-variations-entities-0-actions-ief-entity-remove');
    $button = $this->xpath('//[@data-drupal-selector="edit-variations-form-entities-0-form-actions-ief-remove-confirm"]');
    $button[0]->click();

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = Product::load($product->id());
    $product->save();
    $this->assertEquals(3, count($product->getVariationIds()));
  }

}
