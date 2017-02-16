<?php

namespace Drupal\Tests\commerce_product\Functional;

use Drupal\commerce_product\Entity\ProductAttribute;

/**
 * Tests translating product attributes and their values.
 *
 * @group commerce
 */
class ProductAttributeTranslationTest extends ProductBrowserTestBase {

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
      'translate any entity',
      'translate configuration',
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
    \Drupal::languageManager()->reset();
  }

  /**
   * Tests product attribute translation.
   */
  public function testProductAttributeTranslation() {
    // Create an attribute with no values.
    $this->createEntity('commerce_product_attribute', [
      'id' => 'color',
      'label' => 'Color',
    ]);
    // Confirm that the attribute is translatable, and there's no value
    // translation form is missing.
    $this->drupalGet('admin/commerce/product-attributes/manage/color/translate/fr/add');
    $this->assertSession()->pageTextContains('Label');
    $this->assertSession()->pageTextNotContains('Value');

    // Add two attribute values.
    $red_value = $this->createEntity('commerce_product_attribute_value', [
      'attribute' => 'color',
      'name' => 'Red',
      'weight' => 0,
    ]);
    $blue_value = $this->createEntity('commerce_product_attribute_value', [
      'attribute' => 'color',
      'name' => 'Blue',
      'weight' => 1,
    ]);
    // Confirm that the value translation form is still missing.
    $this->drupalGet('admin/commerce/product-attributes/manage/color/translate/fr/add');
    $this->assertSession()->pageTextNotContains('Value');

    // Enable attribute value translations.
    $edit = [
      'enable_value_translation' => TRUE,
    ];
    $this->drupalGet('admin/commerce/product-attributes/manage/color');
    $this->submitForm($edit, t('Save'));

    // Translate the attribute and its values to French.
    $this->drupalGet('admin/commerce/product-attributes/manage/color/translate/fr/add');
    $this->assertSession()->pageTextContains('Label');
    $this->assertSession()->pageTextContains('Value');
    $edit = [
      'translation[config_names][commerce_product.commerce_product_attribute.color][label]' => 'Couleur',
      'values[' . $red_value->id() . '][translation][name][0][value]' => 'Rouge',
      // Leave the second value untouched.
    ];
    $this->submitForm($edit, t('Save translation'));

    \Drupal::entityTypeManager()->getStorage('commerce_product_attribute')->resetCache();
    \Drupal::entityTypeManager()->getStorage('commerce_product_attribute_value')->resetCache();
    $color_attribute = ProductAttribute::load('color');
    // Confirm the attribute translation.
    $language_manager = \Drupal::languageManager();
    $config_name = $color_attribute->getConfigDependencyName();
    $config_translation = $language_manager->getLanguageConfigOverride('fr', $config_name);
    $this->assertEquals($config_translation->get('label'), 'Couleur');

    // Confirm the attribute value translations.
    $values = $color_attribute->getValues();
    $first_value = reset($values);
    $first_value = $first_value->getTranslation('fr');
    $this->assertEquals($first_value->language()->getId(), 'fr');
    $this->assertEquals($first_value->label(), 'Rouge');
    $second_value = end($values);
    $second_value = $second_value->getTranslation('fr');
    $this->assertEquals($second_value->language()->getId(), 'fr');
    $this->assertEquals($second_value->label(), 'Blue');
  }

}
