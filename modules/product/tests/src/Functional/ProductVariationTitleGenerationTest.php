<?php

namespace Drupal\Tests\commerce_product\Functional;

use Drupal\commerce_product\Entity\ProductVariationType;

/**
 * Tests the product variation title generation.
 *
 * @group commerce
 */
class ProductVariationTitleGenerationTest extends ProductBrowserTestBase {

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
  }

  /**
   * Test the variation type setting.
   */
  public function testTitleGenerationSetting() {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $field_definitions */
    $this->assertEmpty($this->variationType->shouldGenerateTitle());
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('commerce_product_variation', $this->variationType->id());
    $this->assertNotEmpty($field_definitions['title']->isRequired());

    // Enable generation.
    $this->variationType->setGenerateTitle(TRUE);
    $this->variationType->save();
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variationType->id());
    $this->assertNotEmpty($variation_type->shouldGenerateTitle());

    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $field_definitions */
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('commerce_product_variation', $this->variationType->id());
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

    // @todo Create attributes, then retest title generation.
  }

}
