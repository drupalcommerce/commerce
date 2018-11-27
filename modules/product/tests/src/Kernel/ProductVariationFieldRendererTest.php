<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_price\Price;
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

    $user = $this->createUser([], ['administer commerce_product']);
    $this->container->get('current_user')->setAccount($user);
  }

  /**
   * Tests renderFields.
   *
   * @covers ::renderFields
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

    $entity_display = commerce_get_entity_display('commerce_product_variation', $variation->bundle(), 'view');
    $entity_display->setComponent('sku', [
      'label' => 'above',
      'type' => 'string',
    ]);
    $entity_display->setComponent('attribute_color', [
      'label' => 'above',
      'type' => 'entity_reference_label',
    ]);
    $entity_display->setComponent('product_id', [
      'label' => 'above',
      'type' => 'entity_reference_entity_view',
    ]);
    $entity_display->setComponent('list_price', [
      'label' => 'above',
      'type' => 'commerce_price_default',
    ]);
    $entity_display->removeComponent('price');
    $entity_display->save();

    $rendered_fields = $this->variationFieldRenderer->renderFields($variation);
    // The product_id field should be skipped to avoid a render loop.
    $this->assertArrayNotHasKey('product_id', $rendered_fields);
    $this->assertArrayNotHasKey('price', $rendered_fields);
    $this->assertArrayHasKey('sku', $rendered_fields);
    $this->assertArrayHasKey('attribute_color', $rendered_fields);
    $this->assertNotEmpty($rendered_fields['sku']);
    $this->assertNotEmpty($rendered_fields['sku'][0]);
    $this->assertNotEmpty($rendered_fields['attribute_color']);
    $this->assertNotEmpty($rendered_fields['attribute_color'][0]);
    $this->assertEquals('product--variation-field--variation_sku__' . $variation->getProductId(), $rendered_fields['sku']['#ajax_replace_class']);
    $this->assertEquals('product--variation-field--variation_attribute_color__' . $variation->getProductId(), $rendered_fields['attribute_color']['#ajax_replace_class']);
    // Confirm that an empty field gets a rendered wrapper.
    $this->assertArrayHasKey('list_price', $rendered_fields);
    $this->assertNotEmpty($rendered_fields['list_price']);
    $this->assertEquals('product--variation-field--variation_list_price__' . $variation->getProductId(), $rendered_fields['list_price']['#ajax_replace_class']);
    $this->assertEquals('container', $rendered_fields['list_price']['#type']);

    $product_view_builder = $this->container->get('entity_type.manager')->getViewBuilder('commerce_product');
    $product_build = $product_view_builder->view($product);
    $this->render($product_build);

    // Attributes are excluded by default in the twig template.
    $this->assertEmpty($this->cssSelect('.product--variation-field--variation_attribute_color__' . $variation->getProductId()));
    // Verify that the SKU was displayed.
    $this->assertEscaped($variation->getSku());
  }

  /**
   * Tests renderFields in multilingual context.
   *
   * @covers ::renderFields
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

    $entity_display = commerce_get_entity_display('commerce_product_variation', $this->secondVariationType->id(), 'view');
    $entity_display->setComponent('attribute_color', [
      'label' => 'above',
      'type' => 'entity_reference_label',
    ]);
    $entity_display->setComponent('title', [
      'label' => 'above',
      'type' => 'string',
    ]);
    $entity_display->save();

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
   * Tests rendering a single field.
   *
   * @covers ::renderField
   */
  public function testRenderField() {
    $variation = ProductVariation::create([
      'type' => $this->secondVariationType->id(),
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'price' => new Price('10', 'USD'),
      'status' => 1,
    ]);
    $variation->save();
    $product = Product::create([
      'type' => 'default',
      'variations' => [$variation],
    ]);
    $product->save();

    $entity_display = commerce_get_entity_display('commerce_product_variation', $variation->bundle(), 'view');
    $entity_display->setComponent('sku', [
      'label' => 'above',
      'type' => 'string',
    ]);
    $entity_display->setComponent('list_price', [
      'label' => 'above',
      'type' => 'commerce_price_default',
    ]);
    $entity_display->removeComponent('price');
    $entity_display->save();

    $rendered_field = $this->variationFieldRenderer->renderField('sku', $variation, 'default');
    $this->assertNotEmpty($rendered_field);
    $this->assertNotEmpty($rendered_field[0]);
    $this->assertEquals('product--variation-field--variation_sku__' . $variation->getProductId(), $rendered_field['#ajax_replace_class']);

    // Confirm that an empty field gets a rendered wrapper.
    $rendered_field = $this->variationFieldRenderer->renderField('list_price', $variation, 'default');
    $this->assertNotEmpty($rendered_field);
    $this->assertEquals('product--variation-field--variation_list_price__' . $variation->getProductId(), $rendered_field['#ajax_replace_class']);
    $this->assertEquals('container', $rendered_field['#type']);

    // Confirm that hidden fields don't get AJAX classes.
    $rendered_field = $this->variationFieldRenderer->renderField('price', $variation, 'default');
    $this->assertEmpty($rendered_field);

    // Confirm that passing a custom formatter works.
    $rendered_field = $this->variationFieldRenderer->renderField('price', $variation, [
      'type' => 'commerce_price_default',
    ]);
    $this->assertNotEmpty($rendered_field);
    $this->assertNotEmpty($rendered_field[0]);
    $this->assertEquals('product--variation-field--variation_price__' . $variation->getProductId(), $rendered_field['#ajax_replace_class']);
  }

  /**
   * Tests that viewing a product without variations does not throw fatal error.
   *
   * @see commerce_product_commerce_product_view()
   */
  public function testNoVariations() {
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
