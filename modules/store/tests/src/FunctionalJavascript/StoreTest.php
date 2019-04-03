<?php

namespace Drupal\Tests\commerce_store\FunctionalJavascript;

use Drupal\commerce_store\Entity\Store;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests the store UI.
 *
 * @group commerce
 */
class StoreTest extends CommerceWebDriverTestBase {

  /**
   * A store type entity to use in the tests.
   *
   * @var \Drupal\commerce_store\Entity\StoreTypeInterface
   */
  protected $type;

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'access commerce_store overview',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests creating a store.
   */
  public function testCreateStore() {
    $this->drupalGet('admin/commerce/config/stores');
    $this->getSession()->getPage()->clickLink('Add store');

    // Check the integrity of the form.
    $this->assertSession()->fieldExists('name[0][value]');
    $this->assertSession()->fieldExists('mail[0][value]');
    $this->assertSession()->fieldExists('address[0][address][country_code]');
    $this->assertSession()->fieldExists('billing_countries[]');
    $this->assertSession()->fieldExists('uid[0][target_id]');
    $this->assertSession()->fieldExists('default');

    $this->getSession()->getPage()->fillField('address[0][address][country_code]', 'US');
    $this->getSession()->wait(4000, 'jQuery(\'select[name="address[0][address][administrative_area]"]\').length > 0 && jQuery.active == 0;');

    $name = $this->randomMachineName(8);
    $edit = [
      'name[0][value]' => $name,
      'mail[0][value]' => \Drupal::currentUser()->getEmail(),
      'default_currency' => 'USD',
    ];
    $address = [
      'address_line1' => '1098 Alta Ave',
      'locality' => 'Mountain View',
      'administrative_area' => 'CA',
      'postal_code' => '94043',
    ];
    foreach ($address as $property => $value) {
      $path = 'address[0][address][' . $property . ']';
      $edit[$path] = $value;
    }
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains("Saved the $name store.");
    $store_count = $this->getSession()->getPage()->findAll('css', '.view-commerce-stores tr td.views-field-name');
    $this->assertEquals(2, count($store_count));
  }

  /**
   * Tests editing a store.
   */
  public function testEditStore() {
    $store = $this->createStore('Test');
    $this->drupalGet($store->toUrl('edit-form'));
    $edit = [
      'name[0][value]' => 'Test!',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("Saved the Test! store.");

    $store = $this->reloadEntity($store);
    $this->assertEquals('Test!', $store->label());
  }

  /**
   * Tests duplicating a store.
   */
  public function testDuplicateStore() {
    $store = $this->createStore('Test');
    $this->drupalGet($store->toUrl('duplicate-form'));
    $edit = [
      'name[0][value]' => 'Test2',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Saved the Test2 store.');

    // Confirm that the original store is unchanged.
    $store = $this->reloadEntity($store);
    $this->assertEquals('Test', $store->label());

    // Confirm that the new store type has the expected data.
    $store = Store::load($store->id() + 1);
    $this->assertNotEmpty($store);
    $this->assertEquals('Test2', $store->label());
  }

  /**
   * Tests deleting a store.
   */
  public function testDeleteStore() {
    $store = $this->createStore();
    $this->drupalGet($store->toUrl('delete-form'));
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], t('Delete'));

    $this->container->get('entity_type.manager')->getStorage('commerce_store')->resetCache([$store->id()]);
    $store_exists = (bool) Store::load($store->id());
    $this->assertEmpty($store_exists);
  }

}
