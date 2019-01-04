<?php

namespace Drupal\Tests\commerce_product\Functional;

use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Tests\commerce_cart\Traits\CartBrowserTestTrait;
use Drupal\Tests\commerce_product\Traits\ProductAttributeTestTrait;

/**
 * Tests the product variation title generation.
 *
 * @group commerce
 */
class ProductVariationTitleGenerationTest extends ProductBrowserTestBase {

  use CartBrowserTestTrait;
  use ProductAttributeTestTrait;

  /**
   * The variation type to test against.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface
   */
  protected $variationType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->variationType = $this->createEntity('commerce_product_variation_type', [
      'id' => 'test_default',
      'label' => 'Test Default',
      'orderItemType' => 'default',
    ]);
    $this->attributeFieldManager = $this->container->get('commerce_product.attribute_field_manager');
  }

  /**
   * Test the variation type setting.
   */
  public function testTitleGenerationSetting() {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $field_definitions */
    $this->assertEmpty($this->variationType->shouldGenerateTitle());
    $field_definitions = $this->container->get('entity_field.manager')->getFieldDefinitions('commerce_product_variation', $this->variationType->id());
    $this->assertNotEmpty($field_definitions['title']->isRequired());

    // Enable generation.
    $this->variationType->setGenerateTitle(TRUE);
    $this->variationType->save();
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variationType->id());
    $this->assertNotEmpty($variation_type->shouldGenerateTitle());

    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $field_definitions */
    $field_definitions = $this->container->get('entity_field.manager')->getFieldDefinitions('commerce_product_variation', $this->variationType->id());
    $this->assertEmpty($field_definitions['title']->isRequired());
  }

  /**
   * Test the title generation.
   */
  public function testTitleGeneration() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'test_default',
      'sku' => strtolower($this->randomMachineName()),
    ]);
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
    ]);
    $this->assertNotEmpty(empty($variation->getTitle()));

    $this->variationType->setGenerateTitle(TRUE);
    $this->variationType->save();

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'test_default',
      'sku' => strtolower($this->randomMachineName()),
    ]);
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My second product',
      'variations' => [$variation],
    ]);
    $this->assertEquals($variation->getTitle(), $product->getTitle());

    // Adding attributes should change the titles.
    $variation_type = ProductVariationType::load($variation->bundle());
    $size_attributes = $this->createAttributeSet($variation_type, 'size', [
      'small' => 'Small',
      'medium' => 'Medium',
      'large' => 'Large',
    ]);
    $variation = ProductVariation::load($variation->id());
    $variation->attribute_size = $size_attributes['small']->id();
    $variation->save();
    $this->assertEquals($product->getTitle() . ' - Small', $variation->getTitle());

    $color_attributes = $this->createAttributeSet($variation_type, 'color', [
      'red' => 'Red',
    ]);
    $variation = ProductVariation::load($variation->id());
    $variation->attribute_color = $color_attributes['red']->id();
    $variation->save();

    $this->assertEquals($product->getTitle() . ' - Small, Red', $variation->getTitle());
  }

}
