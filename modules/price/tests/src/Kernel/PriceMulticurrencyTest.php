<?php

namespace Drupal\Tests\commerce_price\Kernel;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests price for multicurrency.
 *
 * @group commerce
 */
class PriceMulticurrencyTest extends CommerceKernelTestBase {

  use StoreCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'language',
    'path',
    'commerce_price_test',
    'commerce_product',
  ];

  /**
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation;

  /**
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $productVariationDefaultDisplay;

  /**
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $productVariationViewBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add FR language.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    // Import EUR currency.
    $this->container->get('commerce_price.currency_importer')->import('EUR');

    $this->installEntitySchema('commerce_product_attribute');
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product_variation_type');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_type');
    $this->installConfig(['commerce_product']);

    $this->productVariationDefaultDisplay = commerce_get_entity_display('commerce_product_variation', 'default', 'view');
    $this->productVariationViewBuilder = $this->container->get('entity_type.manager')->getViewBuilder('commerce_product_variation');

    // Create extra Price EUR field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'price_eur',
      'entity_type' => 'commerce_product_variation',
      'type' => 'commerce_price',
      'cardinality' => 1,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'default',
      'label' => 'Price EUR',
      'required' => TRUE,
      'translatable' => FALSE,
    ]);
    $field->save();

    // Create product variation.
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => new Price('12.00', 'USD'),
      'price_eur' => new Price('10.00', 'EUR'),
    ]);
    $variation->save();
    $this->variation = $variation;
  }

  /**
   * Tests the multicurrency price.
   */
  public function testPriceMulticurrency() {
    // Set the calculated price formatter which use the price resolver.
    $this->productVariationDefaultDisplay->setComponent('price', [
      'type' => 'commerce_price_calculated',
      'settings' => [],
    ]);
    $this->productVariationDefaultDisplay->save();

    // Check the default price.
    $build = $this->productVariationViewBuilder->viewField($this->variation->price, 'default');
    $this->render($build);
    $this->assertText('$12.00');

    // Change the language to 'fr'.
    \Drupal::configFactory()->getEditable('system.site')->set('default_langcode', 'fr')->save();
    // Check the price for 'fr' language.
    $build = $this->productVariationViewBuilder->viewField($this->variation->price, 'default');
    $this->render($build);
    $this->assertText('€10.00');
  }

}
