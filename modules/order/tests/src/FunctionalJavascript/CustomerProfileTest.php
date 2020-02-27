<?php

namespace Drupal\Tests\commerce_order\FunctionalJavascript;

use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileType;

/**
 * Tests the customer_profile inline form.
 *
 * @group commerce
 */
class CustomerProfileTest extends OrderWebDriverTestBase {

  /**
   * An empty address.
   *
   * @var array
   */
  protected $emptyAddress = [
    'country_code' => 'RS',
    'locality' => '',
    'postal_code' => '',
    'address_line1' => '',
    'given_name' => '',
    'family_name' => '',
  ];

  /**
   * A French address.
   *
   * @var array
   */
  protected $frenchAddress = [
    'country_code' => 'FR',
    'locality' => 'Paris',
    'postal_code' => '75002',
    'address_line1' => '38 Rue du Sentier',
    'given_name' => 'Leon',
    'family_name' => 'Blum',
  ];

  /**
   * A US address.
   *
   * @var array
   */
  protected $usAddress = [
    'country_code' => 'US',
    'administrative_area' => 'SC',
    'locality' => 'Greenville',
    'postal_code' => '29616',
    'address_line1' => '9 Drupal Ave',
    'given_name' => 'Bryan',
    'family_name' => 'Centarro',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->store->set('address', [
      'country_code' => 'RS',
      'postal_code' => '11000',
      'locality' => 'Belgrade',
      'address_line1' => 'Cetinjska 15',
    ]);
    $this->store->save();
  }

  /**
   * Tests the country handling.
   */
  public function testCountries() {
    // Confirm that the country list has been restricted to available countries.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form');
    $options = $this->xpath('//select[@name="profile[address][0][address][country_code]"]/option');
    $this->assertCount(3, $options);
    $this->assertEquals('FR', $options[0]->getValue());
    $this->assertEquals('RS', $options[1]->getValue());
    $this->assertEquals('US', $options[2]->getValue());
    // Confirm that the store default is selected when available.
    $this->assertNotEmpty($options[1]->getAttribute('selected'));

    // Confirm that it is possible to change the country and submit the form.
    $this->getSession()->getPage()->fillField('profile[address][0][address][country_code]', 'FR');
    $this->assertSession()->assertWaitOnAjaxRequest();
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
   * Tests the address book in "multiple" mode, on a new profile entity.
   */
  public function testMultipleNew() {
    // Test the initial state, with no address book profiles available.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form');
    $this->assertSession()->fieldNotExists('select_address');
    foreach ($this->emptyAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->assertSession()->checkboxChecked('profile[copy_to_address_book]');
    $this->assertSession()->pageTextContains('Save to my address book');
    $this->submitForm([
      'profile[address][0][address][given_name]' => 'John',
      'profile[address][0][address][family_name]' => 'Smith',
      'profile[address][0][address][address_line1]' => 'Cetinjska 13',
      'profile[address][0][address][locality]' => 'Belgrade',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "Cetinjska 13" and the country code is RS. Address book: Yes');

    $us_profile = Profile::create([
      'type' => 'customer',
      'uid' => $this->adminUser->id(),
      'address' => $this->usAddress,
    ]);
    $us_profile->save();
    sleep(1);

    $french_profile = Profile::create([
      'type' => 'customer',
      'uid' => $this->adminUser->id(),
      'address' => $this->frenchAddress,
    ]);
    $french_profile->save();

    // Confirm that the US profile is first and selected, since it is
    // the default.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form');
    $options = $this->xpath('//select[@name="profile[select_address]"]/option');
    $this->assertCount(3, $options);
    $this->assertEquals($this->usAddress['address_line1'], $options[0]->getText());
    $this->assertEquals($this->frenchAddress['address_line1'], $options[1]->getText());
    $this->assertEquals('+ Enter a new address', $options[2]->getText());
    $this->assertNotEmpty($options[0]->getAttribute('selected'));

    // Confirm that the US profile is shown rendered.
    $this->assertRenderedAddress($this->usAddress);
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains('The street is "9 Drupal Ave" and the country code is US. Address book: No');

    // Confirm that it is possible to edit and submit the US profile.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form');
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->usAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    // The checkbox should be hidden and TRUE for every edit operation.
    $this->assertSession()->fieldNotExists('profile[copy_to_address_book]');
    $this->submitForm([
      'profile[address][0][address][address_line1]' => '10 Drupal Ave',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "10 Drupal Ave" and the country code is US. Address book: Yes');

    // Confirm that selecting "Enter a new address" clears the form.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form');
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->usAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->getSession()->getPage()->fillField('profile[select_address]', '_new');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->saveHtmlOutput();
    foreach ($this->emptyAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->submitForm([
      'profile[address][0][address][given_name]' => 'John',
      'profile[address][0][address][family_name]' => 'Smith',
      'profile[address][0][address][address_line1]' => 'Cetinjska 13',
      'profile[address][0][address][locality]' => 'Belgrade',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "Cetinjska 13" and the country code is RS. Address book: Yes');

    // Confirm that it is possible to select the French profile.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form');
    $this->getSession()->getPage()->fillField('profile[select_address]', $french_profile->id());
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertRenderedAddress($this->frenchAddress);
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is FR. Address book: No');

    // Confirm that it is possible to select and edit the French profile.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form');
    $this->getSession()->getPage()->fillField('profile[select_address]', $french_profile->id());
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->frenchAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->assertSession()->fieldNotExists('profile[copy_to_address_book]');
    $this->submitForm([
      'profile[address][0][address][address_line1]' => '37 Rue du Sentier',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "37 Rue du Sentier" and the country code is FR. Address book: Yes');

    // Confirm that selecting a different address reverts to render mode.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form');
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->usAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->getSession()->getPage()->fillField('profile[select_address]', $french_profile->id());
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertRenderedAddress($this->frenchAddress);

    // Confirm that it is possible to add a new profile.
    $this->getSession()->getPage()->fillField('profile[select_address]', '_new');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->emptyAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->assertSession()->checkboxChecked('profile[copy_to_address_book]');
    $this->assertSession()->pageTextContains('Save to my address book');
    $this->submitForm([
      'profile[address][0][address][given_name]' => 'John',
      'profile[address][0][address][family_name]' => 'Smith',
      'profile[address][0][address][address_line1]' => 'Cetinjska 13',
      'profile[address][0][address][locality]' => 'Belgrade',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "Cetinjska 13" and the country code is RS. Address book: Yes');
  }

  /**
   * Tests the address book in "multiple" mode, on an existing profile entity.
   */
  public function testMultipleExisting() {
    // One-off profile, with no address book profiles available.
    $profile = Profile::create([
      'type' => 'customer',
      'uid' => 0,
      'address' => $this->frenchAddress,
    ]);
    $profile->save();
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    $this->assertSession()->fieldNotExists('select_address');
    // Confirm that the french address is shown rendered.
    $this->assertRenderedAddress($this->frenchAddress);
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is FR. Address book: No');

    // Confirm that it is possible to edit the profile.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->frenchAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    // Confirm that since the profile was never copied to the address book,
    // it is still possible to do so.
    $this->assertSession()->checkboxNotChecked('profile[copy_to_address_book]');
    $this->assertSession()->pageTextContains('Save to my address book');
    $this->submitForm([
      'profile[address][0][address][address_line1]' => '37 Rue du Sentier',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "37 Rue du Sentier" and the country code is FR. Address book: No');

    // Profile that is flagged for copying (simulating edit within checkout).
    $profile->setData('copy_to_address_book', TRUE);
    $profile->save();
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    $this->assertRenderedAddress($this->frenchAddress);
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is FR. Address book: Yes');

    // Confirm that it is possible to edit the copy checkbox.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->checkboxChecked('profile[copy_to_address_book]');
    $this->assertSession()->pageTextContains('Save to my address book');
    $this->submitForm([
      'profile[copy_to_address_book]' => FALSE,
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is FR. Address book: No');

    $us_profile = Profile::create([
      'type' => 'customer',
      'uid' => $this->adminUser->id(),
      'address' => $this->usAddress,
    ]);
    $us_profile->save();
    sleep(1);

    $french_profile = Profile::create([
      'type' => 'customer',
      'uid' => $this->adminUser->id(),
      'address' => $this->frenchAddress,
    ]);
    $french_profile->save();

    // Populated from the French profile, which is still identical.
    $profile = Profile::create([
      'type' => 'customer',
      'uid' => 0,
      'address' => $this->frenchAddress,
    ]);
    $profile->populateFromProfile($french_profile);
    $profile->setData('address_book_profile_id', $french_profile->id());
    $profile->save();

    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    // Confirm that there is no _original option, since the profile still
    // matches the address book profile.
    $options = $this->xpath('//select[@name="profile[select_address]"]/option');
    $this->assertCount(3, $options);
    $this->assertEquals($this->usAddress['address_line1'], $options[0]->getText());
    $this->assertEquals($this->frenchAddress['address_line1'], $options[1]->getText());
    $this->assertEquals('+ Enter a new address', $options[2]->getText());
    // Confirm that the address book profile is selected.
    $this->assertNotEmpty($options[1]->getAttribute('selected'));

    $this->assertRenderedAddress($this->frenchAddress);
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is FR. Address book: No');

    // Confirm that it is possible to edit the profile.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->frenchAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->assertSession()->fieldNotExists('profile[copy_to_address_book]');
    $this->submitForm([
      'profile[address][0][address][address_line1]' => '37 Rue du Sentier',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "37 Rue du Sentier" and the country code is FR. Address book: Yes');

    // Populated from the French profile, which has changed since, and has
    // a different street address.
    $profile = Profile::create([
      'type' => 'customer',
      'uid' => 0,
      'address' => $this->frenchAddress,
    ]);
    $profile->setData('address_book_profile_id', $french_profile->id());
    $profile->save();

    $new_french_address = [
      'country_code' => 'FR',
      'locality' => 'Paris',
      'postal_code' => '75002',
      'address_line1' => '39 Rue du Sentier',
      'given_name' => 'Leon',
      'family_name' => 'Blum',
    ];
    $french_profile->set('address', $new_french_address);
    $french_profile->save();

    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    // Confirm that the _original option is present and selected.
    $options = $this->xpath('//select[@name="profile[select_address]"]/option');
    $this->assertCount(4, $options);
    $this->assertEquals($this->usAddress['address_line1'], $options[0]->getText());
    $this->assertEquals($new_french_address['address_line1'], $options[1]->getText());
    $this->assertEquals($this->frenchAddress['address_line1'], $options[2]->getText());
    $this->assertEquals('+ Enter a new address', $options[3]->getText());
    $this->assertNotEmpty($options[2]->getAttribute('selected'));
    $this->assertEquals('_original', $options[2]->getValue());

    $this->assertRenderedAddress($this->frenchAddress);
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is FR. Address book: No');

    // Confirm that it is possible to switch to a different profile.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    $this->getSession()->getPage()->fillField('profile[select_address]', $french_profile->id());
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertRenderedAddress($new_french_address);
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains('The street is "39 Rue du Sentier" and the country code is FR. Address book: No');

    // Confirm that it is possible to switch to a different profile, and back.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    $this->getSession()->getPage()->fillField('profile[select_address]', $french_profile->id());
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertRenderedAddress($new_french_address);
    $this->getSession()->getPage()->fillField('profile[select_address]', '_original');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertRenderedAddress($this->frenchAddress);
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is FR. Address book: No');

    // Confirm that it is possible to edit the profile.
    // Editing the profile should not result in an address book copy, since
    // the source address book profile is now out of sync.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->frenchAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->assertSession()->fieldNotExists('profile[copy_to_address_book]');
    $this->submitForm([
      'profile[address][0][address][address_line1]' => '37 Rue du Sentier',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "37 Rue du Sentier" and the country code is FR. Address book: No');

    // Populated from the French profile, which has changed since, but has
    // the same street address / label.
    $profile = Profile::create([
      'type' => 'customer',
      'uid' => 0,
      'address' => $this->frenchAddress,
    ]);
    $profile->setData('address_book_profile_id', $french_profile->id());
    $profile->save();

    $new_french_address = [
      'country_code' => 'FR',
      'locality' => 'Paris',
      'postal_code' => '75002',
      'address_line1' => '38 Rue du Sentier',
      'given_name' => 'Leon',
      'family_name' => 'Leon',
    ];
    $french_profile->set('address', $new_french_address);
    $french_profile->save();

    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    // Confirm that the _original option is present and selected, and that it
    // has the expected suffix.
    $options = $this->xpath('//select[@name="profile[select_address]"]/option');
    $this->assertCount(4, $options);
    $this->assertEquals($this->usAddress['address_line1'], $options[0]->getText());
    $this->assertEquals($this->frenchAddress['address_line1'], $options[1]->getText());
    $this->assertEquals($this->frenchAddress['address_line1'] . ' (current version)', $options[2]->getText());
    $this->assertEquals('+ Enter a new address', $options[3]->getText());
    $this->assertNotEmpty($options[2]->getAttribute('selected'));
    $this->assertEquals('_original', $options[2]->getValue());

    $this->assertRenderedAddress($new_french_address);
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is FR. Address book: No');

    // Confirm that it is possible to edit the profile.
    // Editing the profile should not result in an address book copy, since
    // the source address book profile is now out of sync.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->frenchAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->assertSession()->fieldNotExists('profile[copy_to_address_book]');
    $this->submitForm([
      'profile[address][0][address][address_line1]' => '37 Rue du Sentier',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "37 Rue du Sentier" and the country code is FR. Address book: No');

    // Populated from a deleted address book profile.
    $profile = Profile::create([
      'type' => 'customer',
      'uid' => 0,
      'address' => $this->frenchAddress,
    ]);
    $profile->setData('address_book_profile_id', '999');
    $profile->save();

    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    $this->assertSession()->fieldValueEquals('profile[select_address]', '_original');
    $this->assertRenderedAddress($this->frenchAddress);
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is FR. Address book: No');

    // Confirm that it is possible to edit the profile.
    // Since there's no matching address book profile ID, the profile should
    // be treated as a one-off (with the checkbox shown to allow copying again).
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->frenchAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->assertSession()->checkboxNotChecked('profile[copy_to_address_book]');
    $this->assertSession()->pageTextContains('Save to my address book');
    $this->submitForm([
      'profile[address][0][address][address_line1]' => '37 Rue du Sentier',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "37 Rue du Sentier" and the country code is FR. Address book: No');
  }

  /**
   * Tests the address book in "multiple" mode, for administrators.
   */
  public function testMultipleAdministrator() {
    // Start from a one-off profile, then add it to the address book.
    $profile = Profile::create([
      'type' => 'customer',
      'uid' => 0,
      'address' => $this->frenchAddress,
    ]);
    $profile->save();

    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id() . '/TRUE');
    $this->assertRenderedAddress($this->frenchAddress);
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->frenchAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->assertSession()->checkboxNotChecked('profile[copy_to_address_book]');
    $this->assertSession()->pageTextContains("Save to the customer's address book");
    $this->submitForm([
      'profile[copy_to_address_book]' => TRUE,
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is FR.');

    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->reloadEntity($profile);
    $this->assertNotEmpty($profile->getData('address_book_profile_id'));
    $address_book_profile_id = $profile->getData('address_book_profile_id');
    $address_book_profile = Profile::load($address_book_profile_id);
    $this->assertTrue($address_book_profile->isDefault());
    $this->assertEquals($this->frenchAddress, array_filter($address_book_profile->get('address')->first()->toArray()));

    // Update the profile.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id() . '/TRUE');
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->frenchAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    // The copy checkbox should be hidden and checked.
    $this->assertSession()->fieldNotExists('profile[copy_to_address_book]');
    $this->submitForm([
      'profile[address][0][address][address_line1]' => '37 Rue du Sentier',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "37 Rue du Sentier" and the country code is FR.');

    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->reloadEntity($profile);
    /** @var \Drupal\profile\Entity\ProfileInterface $address_book_profile */
    $address_book_profile = $this->reloadEntity($address_book_profile);
    $this->assertEquals('37 Rue du Sentier', $profile->get('address')->first()->getAddressLine1());
    $this->assertEquals('37 Rue du Sentier', $address_book_profile->get('address')->first()->getAddressLine1());

    // Replace the profile with a new one.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id() . '/TRUE');
    $this->getSession()->getPage()->fillField('profile[select_address]', '_new');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->emptyAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->assertSession()->checkboxNotChecked('profile[copy_to_address_book]');
    $this->assertSession()->pageTextContains("Save to the customer's address book");
    $this->submitForm([
      'profile[address][0][address][given_name]' => 'John',
      'profile[address][0][address][family_name]' => 'Smith',
      'profile[address][0][address][address_line1]' => 'Cetinjska 13',
      'profile[address][0][address][locality]' => 'Belgrade',
      'profile[copy_to_address_book]' => TRUE,
    ], 'Submit');

    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->reloadEntity($profile);
    /** @var \Drupal\profile\Entity\ProfileInterface $address_book_profile */
    $address_book_profile = $this->reloadEntity($address_book_profile);
    $new_address_book_profile_id = $profile->getData('address_book_profile_id');
    $new_address_book_profile = Profile::load($new_address_book_profile_id);

    // Confirm that the previous address book profile is unchanged.
    $this->assertEquals('37 Rue du Sentier', $address_book_profile->get('address')->first()->getAddressLine1());
    $this->assertNotEquals($new_address_book_profile->id(), $address_book_profile->id());
    // Confirm that the profile and the new address book profile have the
    // expected values.
    $this->assertEquals('Cetinjska 13', $profile->get('address')->first()->getAddressLine1());
    $this->assertEquals('Cetinjska 13', $new_address_book_profile->get('address')->first()->getAddressLine1());

    // Confirm that both address book profiles are now present in the dropdown.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id() . '/TRUE');
    $options = $this->xpath('//select[@name="profile[select_address]"]/option');
    $this->assertCount(3, $options);
    $this->assertEquals('37 Rue du Sentier', $options[0]->getText());
    $this->assertEquals('Cetinjska 13', $options[1]->getText());
    $this->assertEquals('+ Enter a new address', $options[2]->getText());
    $this->assertNotEmpty($options[1]->getAttribute('selected'));

    // Confirm that a deleted address book profile is detected.
    $new_address_book_profile->delete();
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id() . '/TRUE');
    $options = $this->xpath('//select[@name="profile[select_address]"]/option');
    $this->assertCount(3, $options);
    $this->assertEquals('37 Rue du Sentier', $options[0]->getText());
    $this->assertEquals('Cetinjska 13', $options[1]->getText());
    $this->assertEquals('+ Enter a new address', $options[2]->getText());
    $this->assertNotEmpty($options[1]->getAttribute('selected'));
    $this->assertEquals('_original', $options[1]->getValue());

    $rendered_address = [
      'country_code' => 'RS',
      'locality' => 'Belgrade',
      'address_line1' => 'Cetinjska 13',
      'given_name' => 'John',
      'family_name' => 'Smith',
    ];
    $this->assertRenderedAddress($rendered_address);
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->checkboxNotChecked('profile[copy_to_address_book]');
    $this->assertSession()->pageTextContains("Save to the customer's address book");
  }

  /**
   * Tests the address book in "single" mode, on a new profile entity.
   */
  public function testSingleNew() {
    /** @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
    $profile_type = ProfileType::load('customer');
    $profile_type->setMultiple(FALSE);
    $profile_type->save();

    // Confirm that without a default profile, an empty form is shown.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form');
    foreach ($this->emptyAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->assertSession()->fieldNotExists('profile[copy_to_address_book]');

    // Confirm that the form can be submitted.
    $this->submitForm([
      'profile[address][0][address][given_name]' => 'John',
      'profile[address][0][address][family_name]' => 'Smith',
      'profile[address][0][address][address_line1]' => 'Cetinjska 13',
      'profile[address][0][address][locality]' => 'Belgrade',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "Cetinjska 13" and the country code is RS. Address book: Yes');

    // Create a default profile for the current user.
    $default_profile = Profile::create([
      'type' => 'customer',
      'uid' => $this->adminUser->id(),
      'address' => $this->frenchAddress,
    ]);
    $default_profile->save();

    // Confirm that the profile is rendered, and no address fields are present.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form');
    $this->assertRenderedAddress($this->frenchAddress);
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is FR. Address book: No');

    // Confirm that it is possible to edit the profile.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form');
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->frenchAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->assertSession()->fieldNotExists('profile[copy_to_address_book]');
    $this->submitForm([
      'profile[address][0][address][address_line1]' => '37 Rue du Sentier',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "37 Rue du Sentier" and the country code is FR. Address book: Yes');
  }

  /**
   * Tests the address book in "single" mode, on an existing profile entity.
   *
   * In "single" mode the default address book profile always contains the last
   * entered address, requiring every customer edit to result in a copy, even if
   * the profile was standalone, or referencing a deleted address book profile.
   */
  public function testSingleExisting() {
    /** @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
    $profile_type = ProfileType::load('customer');
    $profile_type->setMultiple(FALSE);
    $profile_type->save();

    // Standalone profile.
    $profile = Profile::create([
      'type' => 'customer',
      'uid' => 0,
      'address' => $this->frenchAddress,
    ]);
    $profile->save();
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    $this->assertSession()->fieldNotExists('select_address');
    // Confirm that the french address is shown rendered.
    $this->assertRenderedAddress($this->frenchAddress);
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is FR. Address book: No');

    // Confirm that the profile can be edited.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->frenchAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->assertSession()->fieldNotExists('profile[copy_to_address_book]');
    $this->submitForm([
      'profile[address][0][address][address_line1]' => '37 Rue du Sentier',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "37 Rue du Sentier" and the country code is FR. Address book: Yes');

    $us_profile = Profile::create([
      'type' => 'customer',
      'uid' => $this->adminUser->id(),
      'address' => $this->usAddress,
    ]);
    $us_profile->save();

    $profile = Profile::create([
      'type' => 'customer',
      'uid' => 0,
    ]);
    $profile->populateFromProfile($us_profile);
    $profile->setData('address_book_profile_id', $us_profile->id());
    $profile->save();

    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    $this->assertSession()->fieldNotExists('select_address');
    // Confirm that the US address is shown rendered.
    $this->assertRenderedAddress($this->usAddress);
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains('The street is "9 Drupal Ave" and the country code is US. Address book: No');

    // Confirm that the profile can be edited.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->usAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->assertSession()->fieldNotExists('profile[copy_to_address_book]');
    $this->submitForm([
      'profile[address][0][address][address_line1]' => '10 Drupal Ave',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "10 Drupal Ave" and the country code is US. Address book: Yes');

    // Populated from a deleted address book profile.
    $profile = Profile::create([
      'type' => 'customer',
      'uid' => 0,
      'address' => $this->frenchAddress,
    ]);
    $profile->setData('address_book_profile_id', '999');
    $profile->save();

    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    $this->assertSession()->fieldNotExists('select_address');
    $this->assertRenderedAddress($this->frenchAddress);
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is FR. Address book: No');

    // Confirm that it is possible to edit the profile.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id());
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->frenchAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->assertSession()->fieldNotExists('select_address');
    $this->submitForm([
      'profile[address][0][address][address_line1]' => '37 Rue du Sentier',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "37 Rue du Sentier" and the country code is FR. Address book: Yes');
  }

  /**
   * Tests the address book in "single" mode, for administrators.
   */
  public function testSingleAdministrator() {
    /** @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
    $profile_type = ProfileType::load('customer');
    $profile_type->setMultiple(FALSE);
    $profile_type->save();

    // Start from a one-off profile, then add it to the address book.
    $profile = Profile::create([
      'type' => 'customer',
      'uid' => 0,
      'address' => $this->frenchAddress,
    ]);
    $profile->save();

    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id() . '/TRUE');
    $this->assertSession()->fieldNotExists('select_address');
    $this->assertRenderedAddress($this->frenchAddress);
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->frenchAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->assertSession()->checkboxNotChecked('profile[copy_to_address_book]');
    $this->assertSession()->pageTextContains("Save to the customer's address book");
    $this->submitForm([
      'profile[copy_to_address_book]' => '1',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "38 Rue du Sentier" and the country code is FR.');

    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->reloadEntity($profile);
    $this->assertNotEmpty($profile->getData('address_book_profile_id'));
    $address_book_profile_id = $profile->getData('address_book_profile_id');
    $address_book_profile = Profile::load($address_book_profile_id);
    $this->assertTrue($address_book_profile->isDefault());
    $this->assertEquals($this->frenchAddress, array_filter($address_book_profile->get('address')->first()->toArray()));

    // Update the profile.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id() . '/TRUE');
    $this->assertSession()->fieldNotExists('select_address');
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->frenchAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->assertSession()->checkboxNotChecked('profile[copy_to_address_book]');
    $this->assertSession()->pageTextContains("Also update the address in the customer's address book");
    $this->submitForm([
      'profile[address][0][address][address_line1]' => '37 Rue du Sentier',
      'profile[copy_to_address_book]' => TRUE,
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "37 Rue du Sentier" and the country code is FR.');

    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->reloadEntity($profile);
    /** @var \Drupal\profile\Entity\ProfileInterface $address_book_profile */
    $address_book_profile = $this->reloadEntity($address_book_profile);
    $this->assertEquals('37 Rue du Sentier', $profile->get('address')->first()->getAddressLine1());
    $this->assertEquals('37 Rue du Sentier', $address_book_profile->get('address')->first()->getAddressLine1());

    // Confirm that a deleted address book profile is detected.
    $address_book_profile->delete();
    $this->drupalGet('/commerce_order_test/customer_profile_test_form/' . $profile->id() . '/TRUE');
    $rendered_address = [
      'address_line1' => '37 Rue du Sentier',
    ] + $this->frenchAddress;
    $this->assertRenderedAddress($rendered_address);
    $this->getSession()->getPage()->pressButton('billing_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->checkboxNotChecked('profile[copy_to_address_book]');
    $this->assertSession()->pageTextContains("Save to the customer's address book");
  }

  /**
   * Tests the address book for anonymous customers.
   */
  public function testAnonymous() {
    $this->drupalLogout();
    // Test the address book form when multiple profiles are allowed.
    $this->drupalGet('/commerce_order_test/customer_profile_test_form');
    $this->assertSession()->fieldNotExists('select_address');
    // Confirm that the address fields are shown and not pre-filled.
    foreach ($this->emptyAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
    $this->assertSession()->fieldNotExists('profile[copy_to_address_book]');

    // Confirm value.
    $this->submitForm([
      'profile[address][0][address][given_name]' => 'John',
      'profile[address][0][address][family_name]' => 'Smith',
      'profile[address][0][address][address_line1]' => 'Cetinjska 13',
      'profile[address][0][address][locality]' => 'Belgrade',
    ], 'Submit');
    $this->assertSession()->pageTextContains('The street is "Cetinjska 13" and the country code is RS. Address book: Yes');

    // Test the address book form when only a single profile is allowed.
    /** @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
    $profile_type = ProfileType::load('customer');
    $profile_type->setMultiple(FALSE);
    $profile_type->save();

    $this->drupalGet('/commerce_order_test/customer_profile_test_form');
    // Confirm that the address fields are shown and not pre-filled.
    foreach ($this->emptyAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("profile[address][0][address][$property]", $value);
    }
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
