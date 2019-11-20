<?php

namespace Drupal\Tests\commerce_product\Kernel\Views;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the commerce_product_attribute_value views filter.
 *
 * @group commerce
 */
class ProductAttributeValueFilterTest extends ViewsKernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'address',
    'commerce',
    'commerce_price',
    'commerce_product',
    'commerce_product_test',
    'commerce_store',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_product_variations'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    ViewTestData::createTestViews(self::class, ['commerce_product_test']);
  }

  /**
   * Tests that the created view has a valid configuration.
   */
  public function testViewConfig() {
    $view = Views::getView('test_product_variations');
    $view->initDisplay();

    $view->displayHandlers->get('default')->overrideOption('filters', [
      'attribute_shirt_target_id' => [
        'id' => 'attribute_shirt_target_id',
        'table' => 'commerce_product_variation__attribute_shirt',
        'field' => 'attribute_shirt_target_id',
        'relationship' => 'none',
        'group_type' => 'group',
        'admin_label' => '',
        'operator' => 'in',
        'value' => [],
        'group' => '1',
        'exposed' => FALSE,
        'plugin_id' => 'commerce_product_attribute_value',
      ],
    ]);
    $view->save();
    $this->assertConfigSchemaByName('views.view.test_product_variations');
  }

}
