<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the product variation field renderer.
 *
 * @coversDefaultClass \Drupal\commerce_product\ProductVariationFieldRenderer
 *
 * @group commerce
 */
class ProductVariationFieldRendererTest extends CommerceKernelTestBase {

  /**
   * The variation field injection.
   *
   * @var \Drupal\commerce_product\ProductVariationFieldRendererInterface
   */
  protected $variationFieldRenderer;

  /**
   * The first variation type.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationType
   */
  protected $firstVariationType;

  /**
   * The second variation type.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationType
   */
  protected $secondVariationType;

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
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->installConfig(['commerce_product']);

    ConfigurableLanguage::createFromLangcode('fr')->save();
    // We must have two languages installed. Otherwise it is not considered
    // multilingual because `en` is not installed as a configurable language.
    // @see \Drupal\language\ConfigurableLanguageManager::isMultilingual
    ConfigurableLanguage::createFromLangcode('de')->save();

    $this->variationFieldRenderer = $this->container->get('commerce_product.variation_field_renderer');

    $this->firstVariationType = ProductVariationType::create([
      'id' => 'shirt',
      'label' => 'Shirt',
    ]);
    $this->firstVariationType->save();
    $this->secondVariationType = ProductVariationType::create([
      'id' => 'mug',
      'label' => 'Mug',
    ]);
    $this->secondVariationType->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'render_field',
      'entity_type' => 'commerce_product_variation',
      'type' => 'text',
      'cardinality' => 1,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->secondVariationType->id(),
      'label' => 'Render field',
      'required' => TRUE,
      'translatable' => FALSE,
    ]);
    $field->save();

    $attribute = ProductAttribute::create([
      'id' => 'color',
      'label' => 'Color',
    ]);
    $attribute->save();

    $this->container->get('commerce_product.attribute_field_manager')->createField($attribute, $this->secondVariationType->id());
  }

  /**
   * Tests the getFieldDefinitions method.
   *
   * @covers ::getFieldDefinitions
   */
  public function testGetFieldDefinitions() {
    $field_definitions = $this->variationFieldRenderer->getFieldDefinitions($this->firstVariationType->id());
    $field_names = array_keys($field_definitions);
    $this->assertEquals(['sku', 'title', 'price'], $field_names, 'The title, sku, price variation fields are renderable.');

    $field_definitions = $this->variationFieldRenderer->getFieldDefinitions($this->secondVariationType->id());
    $field_names = array_keys($field_definitions);
    $this->assertEquals(
      ['sku', 'title', 'price', 'render_field', 'attribute_color'],
      $field_names,
      'The title, sku, price, render_field, attribute_color variation fields are renderable.'
    );
  }

  /**
   * Tests renderFields.
   *
   * @covers ::renderFields
   * @covers ::renderField
   */
  public function testRenderFields() {
    $attribute_value = ProductAttributeValue::create([
      'attribute' => 'color',
      'name' => 'Blue',
    ]);
    $attribute_value->save();
    $variation = ProductVariation::create([
      'type' => $this->secondVariationType->id(),
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'attribute_color' => $attribute_value->id(),
      'status' => 1,
    ]);
    $variation->save();
    $product = Product::create([
      'type' => 'default',
      'variations' => [$variation],
    ]);
    $product->save();

    $product_display = commerce_get_entity_display('commerce_product_variation', $variation->bundle(), 'view');
    $product_display->setComponent('attribute_color', [
      'label' => 'above',
      'type' => 'entity_reference_label',
    ]);
    $product_display->setComponent('title', [
      'label' => 'above',
      'type' => 'string',
    ]);
    $product_display->save();

    $rendered_fields = $this->variationFieldRenderer->renderFields($variation);
    $this->assertEmpty(isset($rendered_fields['status']), 'Variation status field was not rendered');
    $this->assertNotEmpty(isset($rendered_fields['sku']), 'Variation SKU field was rendered');
    $this->assertNotEmpty(isset($rendered_fields['attribute_color']), 'Variation atrribute color field was rendered');
    $this->assertEquals('product--variation-field--variation_sku__' . $variation->getProductId(), $rendered_fields['sku']['#ajax_replace_class']);
    $this->assertEquals('product--variation-field--variation_attribute_color__' . $variation->getProductId(), $rendered_fields['attribute_color']['#ajax_replace_class']);

    $product_view_builder = $this->container->get('entity_type.manager')->getViewBuilder('commerce_product');
    $product_build = $product_view_builder->view($product);
    $this->render($product_build);

    // Attributes are excluded by default in twig template, verify.
    $this->assertEmpty($this->cssSelect('.product--variation-field--variation_attribute_color__' . $variation->getProductId()));
    $this->assertEmpty($this->cssSelect('.product--variation-field--variation_sku__' . $variation->getProductId()));
    // Verify that the title was displayed.
    $this->assertEscaped($variation->label(), 'Variation title was injected and displayed.');
  }

  /**
   * Tests renderFields in multilingual context.
   *
   * @covers ::renderFields
   * @covers ::renderField
   */
  public function testRenderFieldsMultilingual() {
    $this->secondVariationType->setGenerateTitle(TRUE);
    $this->secondVariationType->save();

    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product_variation', $this->secondVariationType->id(), TRUE);
    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product', 'default', TRUE);
    $this->container->get('content_translation.manager')
      ->setEnabled('commerce_product_attribute_value', 'color', TRUE);

    $blue = ProductAttributeValue::create([
      'attribute' => 'color',
      'name' => 'Blue',
    ]);
    $blue->addTranslation('fr', [
      'name' => 'Bleu',
    ]);
    $blue->save();
    $black = ProductAttributeValue::create([
      'attribute' => 'color',
      'name' => 'Black',
      'weight' => 3,
    ]);
    $black->addTranslation('fr', [
      'name' => 'Noir',
    ]);
    $black->save();

    $variation1 = ProductVariation::create([
      'type' => $this->secondVariationType->id(),
      'sku' => strtolower($this->randomMachineName()),
      'attribute_color' => $blue->id(),
      'status' => 1,
    ]);
    $variation1->save();
    $variation2 = ProductVariation::create([
      'type' => $this->secondVariationType->id(),
      'sku' => strtolower($this->randomMachineName()),
      'attribute_color' => $black->id(),
      'status' => 1,
    ]);
    $variation2->save();
    $product = Product::create([
      'type' => 'default',
      'title' => 'My Super Product',
      'variations' => [$variation1, $variation2],
    ]);
    $product->addTranslation('fr', [
      'title' => 'Mon super produit',
    ]);
    $product->save();

    $variation1->addTranslation('fr', [])->save();
    $variation2->addTranslation('fr', [])->save();
    $this->assertEquals('Mon super produit - Bleu', $variation1->getTranslation('fr')->label());
    $this->assertEquals('Mon super produit - Noir', $variation2->getTranslation('fr')->label());

    $product_display = commerce_get_entity_display('commerce_product_variation', $this->secondVariationType->id(), 'view');
    $product_display->setComponent('attribute_color', [
      'label' => 'above',
      'type' => 'entity_reference_label',
    ]);
    $product_display->setComponent('title', [
      'label' => 'above',
      'type' => 'string',
    ]);
    $product_display->save();

    // Make sure loadFromContext does not return the default variation, which is
    // always translated via ::getDefaultVariation on the product entity.
    $request = Request::create('');
    $request->query->add([
      'v' => $variation2->id(),
    ]);
    // Push the request to the request stack so `current_route_match` works.
    $this->container->get('request_stack')->push($request);
    $this->assertNotEquals($request->query->get('v'), $product->getDefaultVariation()->id());

    $this->config('system.site')->set('default_langcode', 'fr')->save();

    $product_view_builder = $this->container->get('entity_type.manager')->getViewBuilder('commerce_product');
    $product_build = $product_view_builder->view($product);
    $this->render($product_build);

    $this->assertEscaped('Mon super produit');
    $this->assertEscaped('Mon super produit - Noir');
  }

  /**
   * Tests that viewing a product without variations does not throw fatal error.
   *
   * @see commerce_product_commerce_product_view()
   */
  public function testRenderFieldsNoVariations() {
    $product = Product::create([
      'type' => 'default',
      'variations' => [],
    ]);
    $product->save();

    // The test will fail if we get a fatal error.
    $product_view_builder = $this->container->get('entity_type.manager')->getViewBuilder('commerce_product');
    $product_build = $product_view_builder->view($product);
    $this->render($product_build);
  }

}
