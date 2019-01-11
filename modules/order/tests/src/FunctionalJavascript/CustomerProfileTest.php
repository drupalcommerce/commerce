<?php

namespace Drupal\Tests\commerce_order\FunctionalJavascript;

/**
 * Tests the customer_profile inline form.
 *
 * @group commerce
 */
class CustomerProfileTest extends OrderWebDriverTestBase {

  /**
   * Tests the form.
   */
  public function testForm() {
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
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is "FR".');
  }

}
