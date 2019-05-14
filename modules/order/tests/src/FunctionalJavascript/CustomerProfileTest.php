<?php

namespace Drupal\Tests\commerce_order\FunctionalJavascript;

use Drupal\profile\Entity\Profile;

/**
 * Tests the customer_profile inline form.
 *
 * @group commerce
 */
class CustomerProfileTest extends OrderWebDriverTestBase {

  /**
   * Tests the country handling.
   */
  public function testCountries() {
    // Confirm that the country list has been restricted to available countries.
    // The store default "US" is not present because it is not available.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form');
    $options = $this->xpath('//select[@name="profile[address][0][address][country_code]"]/option');
    $this->assertCount(2, $options);
    $this->assertTrue($options[0]->getAttribute('selected'));
    $this->assertEquals('FR', $options[0]->getValue());
    $this->assertEquals('RS', $options[1]->getValue());

    // Confirm that the store default is selected when available.
    $this->store->set('address', [
      'country_code' => 'RS',
      'postal_code' => '11000',
      'locality' => 'Belgrade',
      'address_line1' => 'Cetinjska 15',
    ]);
    $this->store->save();
    $this->drupalGet('/commerce_order_test/customer_profile_test_form');
    $options = $this->xpath('//select[@name="profile[address][0][address][country_code]"]/option');
    $this->assertCount(2, $options);
    $this->assertTrue($options[1]->getAttribute('selected'));
    $this->assertEquals('FR', $options[0]->getValue());
    $this->assertEquals('RS', $options[1]->getValue());

    // Confirm that it is possible to change the country and submit the form.
    $this->getSession()->getPage()->fillField('profile[address][0][address][country_code]', 'FR');
    $this->waitForAjaxToFinish();
    $this->submitForm([
      'profile[address][0][address][given_name]' => 'Leon',
      'profile[address][0][address][family_name]' => 'Blum',
      'profile[address][0][address][address_line1]' => '38 Rue du Sentier',
      'profile[address][0][address][postal_code]' => '75002',
      'profile[address][0][address][locality]' => 'Paris',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is FR.');
  }

  /**
   * Tests the address book functionality.
   */
  public function testAddressBook() {
    $this->store->set('address', [
      'country_code' => 'RS',
      'postal_code' => '11000',
      'locality' => 'Belgrade',
      'address_line1' => 'Cetinjska 15',
    ]);
    $this->store->save();

    // Create a default profile for the current user.
    $french_address = [
      'country_code' => 'FR',
      'locality' => 'Paris',
      'postal_code' => '75002',
      'address_line1' => '38 Rue du Sentier',
      'given_name' => 'Leon',
      'family_name' => 'Blum',
    ];
    $default_profile = Profile::create([
      'type' => 'customer',
      'uid' => $this->adminUser->id(),
      'address' => $french_address,
    ]);
    $default_profile->save();

    // Confirm that address is pre-filled on the form.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form');
    foreach ($french_address as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    // Confirm that the address book checkbox is shown and checked.
    $this->assertSession()->fieldExists('profile[copy_to_address_book]');
    $this->assertSession()->checkboxChecked('profile[copy_to_address_book]');

    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is FR. Address book: Yes');

    // Confirm that unchecking the checkbox works.
    $this->submitForm([
      'profile[copy_to_address_book]' => FALSE,
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is FR. Address book: No');

    // Test the form with an anonymous user.
    $this->drupalLogout();
    $this->drupalGet('/commerce_order_test/customer_profile_test_form');

    // Confirm that no address is pre-filled.
    $expected_address = [
      'country_code' => 'RS',
      'locality' => '',
      'postal_code' => '',
      'address_line1' => '',
      'given_name' => '',
      'family_name' => '',
    ];
    foreach ($expected_address as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    // Confirm that the address book checkbox is not shown.
    $this->assertSession()->fieldNotExists('profile[copy_to_address_book]');

    // Confirm value.
    $this->submitForm([
      'profile[address][0][address][given_name]' => 'John',
      'profile[address][0][address][family_name]' => 'Smith',
      'profile[address][0][address][address_line1]' => 'Cetinjska 13',
      'profile[address][0][address][locality]' => 'Belgrade',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "Cetinjska 13" and the country code is RS. Address book: Yes');
  }

}
