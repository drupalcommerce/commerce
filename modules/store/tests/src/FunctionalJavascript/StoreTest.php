<?php

namespace Drupal\Tests\commerce_store\FunctionalJavascript;

use Drupal\commerce_store\Entity\Store;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;

/**
 * Create, view, edit, delete, and change store entities.
 *
 * @group commerce
 */
class StoreTest extends CommerceBrowserTestBase {

  use JavascriptTestTrait;

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
    $store_count = $this->getSession()->getPage()->find('css', '.view-commerce-stores tr td.views-field-name');
    $this->assertEquals(count($store_count), 1, 'Stores exists in the table.');
  }

  /**
   * Tests editing a store.
   */
  public function testEditStore() {
    $store = $this->createStore();

    $this->drupalGet($store->toUrl('edit-form'));
    $new_store_name = $this->randomMachineName(8);
    $edit = [
      'name[0][value]' => $new_store_name,
    ];
    $this->submitForm($edit, 'Save');

    \Drupal::service('entity_type.manager')->getStorage('commerce_store')->resetCache([$store->id()]);
    $store_changed = Store::load($store->id());
    $this->assertEquals($new_store_name, $store_changed->getName(), 'The store name successfully updated.');
  }

  /**
   * Tests deleting a store.
   */
  public function testDeleteStore() {
    $store = $this->createStore();
    $this->drupalGet($store->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], t('Delete'));

    \Drupal::service('entity_type.manager')->getStorage('commerce_store')->resetCache([$store->id()]);
    $store_exists = (bool) Store::load($store->id());
    $this->assertEmpty($store_exists, 'The new store has been deleted from the database using UI.');
  }

}
