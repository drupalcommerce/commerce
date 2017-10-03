<?php

namespace Drupal\Tests\commerce_order\FunctionalJavascript;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;

/**
 * Tests the ProfileSelect form element.
 *
 * @group commerce
 */
class ProfileSelectTest extends CommerceBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * Profile address values.
   *
   * @var array
   */
  protected $address1 = [
    'country_code' => 'HU',
    'given_name' => 'Gustav',
    'family_name' => 'Mahler',
    'address_line1' => 'Teréz körút 7',
    'locality' => 'Budapest',
    'postal_code' => '1067',
  ];

  /**
   * Profile address values.
   *
   * @var array
   */
  protected $address2 = [
    'country_code' => 'DE',
    'given_name' => 'Johann Sebastian',
    'family_name' => 'Bach',
    'address_line1' => 'Thomaskirchhof 15',
    'locality' => 'Leipzig',
    'postal_code' => '04109',
  ];

  /**
   * @var \Drupal\profile\ProfileStorageInterface
   */
  protected $profileStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_order_test',
  ];

  /**
   * @inheritDoc
   */
  protected function setUp() {
    parent::setUp();
    $this->profileStorage = $this->container->get('entity_type.manager')->getStorage('profile');
  }

  /**
   * Tests the profile select form element for anonymous user.
   */
  public function testAnonymous() {
    $this->drupalLogout();
    $address_fields = $this->address1;
    $this->drupalGet(Url::fromRoute('commerce_order_test.profile_select_form'));
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->fieldNotExists('Select a profile');
    $this->getSession()->getPage()->fillField('Country', $address_fields['country_code']);
    $this->waitForAjaxToFinish();

    $edit = [];
    foreach ($address_fields as $key => $value) {
      if ($key == 'country_code') {
        continue;
      }
      $edit['profile[address][0][address][' . $key . ']'] = $value;
    }

    $this->submitForm($edit, 'Submit');

    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->profileStorage->load(1);

    $this->assertSession()->responseContains(new FormattableMarkup('Profile selected: :label', [':label' => $profile->label()]));

    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
    $address = $profile->get('address')->first();
    $this->assertEquals($address_fields['country_code'], $address->getCountryCode());
    $this->assertEquals($address_fields['given_name'], $address->getGivenName());
    $this->assertEquals($address_fields['family_name'], $address->getFamilyName());
    $this->assertEquals($address_fields['address_line1'], $address->getAddressLine1());
    $this->assertEquals($address_fields['locality'], $address->getLocality());
    $this->assertEquals($address_fields['postal_code'], $address->getPostalCode());
  }

  /**
   * Tests the profile select form element for anonymous user.
   */
  public function testAuthenticatedNoExistingProfiles() {
    $account = $this->createUser();
    $this->drupalLogin($account);

    $address_fields = $this->address1;
    $this->drupalGet(Url::fromRoute('commerce_order_test.profile_select_form'));
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->fieldNotExists('Select a profile');
    $this->getSession()->getPage()->fillField('Country', $address_fields['country_code']);
    $this->waitForAjaxToFinish();

    $edit = [];
    foreach ($address_fields as $key => $value) {
      if ($key == 'country_code') {
        continue;
      }
      $edit['profile[address][0][address][' . $key . ']'] = $value;
    }

    $this->submitForm($edit, 'Submit');

    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->profileStorage->load(1);

    $this->assertSession()->responseContains(new FormattableMarkup('Profile selected: :label', [':label' => $profile->label()]));

    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
    $address = $profile->get('address')->first();
    $this->assertEquals($address_fields['country_code'], $address->getCountryCode());
    $this->assertEquals($address_fields['given_name'], $address->getGivenName());
    $this->assertEquals($address_fields['family_name'], $address->getFamilyName());
    $this->assertEquals($address_fields['address_line1'], $address->getAddressLine1());
    $this->assertEquals($address_fields['locality'], $address->getLocality());
    $this->assertEquals($address_fields['postal_code'], $address->getPostalCode());
  }

  /**
   * Tests the profile select form element for authenticated user.
   */
  public function testProfileSelectAuthenticated() {
    $account = $this->createUser();

    $profile_storage = $this->container->get('entity_type.manager')->getStorage('profile');
    /** @var \Drupal\profile\Entity\ProfileInterface $profile_address1 */
    $profile_address1 = $profile_storage->create([
      'type' => 'customer',
      'uid' => $account->id(),
      'address' => $this->address1,
    ]);
    $profile_address1->save();
    /** @var \Drupal\profile\Entity\ProfileInterface $profile_address2 */
    $profile_address2 = $profile_storage->create([
      'type' => 'customer',
      'uid' => $account->id(),
      'address' => $this->address2,
    ]);
    $profile_address2->setDefault(TRUE);
    $profile_address2->save();

    $this->drupalLogin($account);
    $this->drupalGet(Url::fromRoute('commerce_order_test.profile_select_form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('Select a profile');
    // The last created profile should be selected by default.
    $this->assertSession()->pageTextContains($this->address2['locality']);

    $this->getSession()->getPage()->fillField('Select a profile', $profile_address1->id());
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextContains($this->address1['locality']);
    $this->submitForm([], 'Submit');
    $this->assertSession()->responseContains(new FormattableMarkup('Profile selected: :label', [':label' => $profile_address1->label()]));

    $profile_storage->resetCache([$profile_address1->id()]);
    $profile_address1 = $profile_storage->load($profile_address1->id());
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
    $address = $profile_address1->get('address')->first();
    // Assert that field values have not changed.
    $this->assertEquals($this->address1['country_code'], $address->getCountryCode());
    $this->assertEquals($this->address1['given_name'], $address->getGivenName());
    $this->assertEquals($this->address1['family_name'], $address->getFamilyName());
    $this->assertEquals($this->address1['address_line1'], $address->getAddressLine1());
    $this->assertEquals($this->address1['locality'], $address->getLocality());
    $this->assertEquals($this->address1['postal_code'], $address->getPostalCode());
  }

  /**
   * Tests the profile select form element for authenticated user.
   */
  public function testProfileSelectAuthenticatedCreateNew() {
    $account = $this->createUser();
    $address_fields = $this->address2;
    /** @var \Drupal\profile\Entity\ProfileInterface $profile_address1 */
    $profile_address1 = $this->profileStorage->create([
      'type' => 'customer',
      'uid' => $account->id(),
      'address' => $this->address1,
    ]);
    $profile_address1->save();

    $this->drupalLogin($account);
    $this->drupalGet(Url::fromRoute('commerce_order_test.profile_select_form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('Select a profile');
    // The last created profile should be selected by default.
    $this->assertSession()->pageTextContains($this->address1['locality']);

    $this->getSession()->getPage()->fillField('Select a profile', '_new');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->fillField('Country', $address_fields['country_code']);
    $this->waitForAjaxToFinish();
    $edit = [];
    foreach ($address_fields as $key => $value) {
      if ($key == 'country_code') {
        continue;
      }
      $edit['profile[address][0][address][' . $key . ']'] = $value;
    }

    $this->submitForm($edit, 'Submit');

    $new_profile = $this->profileStorage->load(2);
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
    $address = $new_profile->get('address')->first();

    $this->assertSession()->responseContains(new FormattableMarkup('Profile selected: :label', [':label' => $new_profile->label()]));
    // Assert that field values have not changed.
    $this->assertEquals($this->address2['country_code'], $address->getCountryCode());
    $this->assertEquals($this->address2['given_name'], $address->getGivenName());
    $this->assertEquals($this->address2['family_name'], $address->getFamilyName());
    $this->assertEquals($this->address2['address_line1'], $address->getAddressLine1());
    $this->assertEquals($this->address2['locality'], $address->getLocality());
    $this->assertEquals($this->address2['postal_code'], $address->getPostalCode());
  }

  /**
   * Tests the profile select form element for authenticated user.
   *
   * @group debug
   */
  public function testProfileSelectAuthenticatedEdit() {
    $account = $this->createUser();
    /** @var \Drupal\profile\Entity\ProfileInterface $profile_address1 */
    $profile_address1 = $this->profileStorage->create([
      'type' => 'customer',
      'uid' => $account->id(),
      'address' => $this->address1,
    ]);
    $profile_address1->save();
    /** @var \Drupal\profile\Entity\ProfileInterface $profile_address2 */
    $profile_address2 = $this->profileStorage->create([
      'type' => 'customer',
      'uid' => $account->id(),
      'address' => $this->address2,
    ]);
    $profile_address2->setDefault(TRUE);
    $profile_address2->save();

    $this->drupalLogin($account);
    $this->drupalGet(Url::fromRoute('commerce_order_test.profile_select_form'));
    $this->assertSession()->statusCodeEquals(200);

    // Edit a profile.
    $this->drupalGet(Url::fromRoute('commerce_order_test.profile_select_form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('Select a profile');
    // The last created profile should be selected by default.
    $this->assertSession()->pageTextContains($this->address2['locality']);
    $this->getSession()->getPage()->pressButton('Edit');
    $this->waitForAjaxToFinish();

    foreach ($this->address2 as $key => $value) {
      $this->assertSession()->fieldValueEquals('profile[address][0][address][' . $key . ']', $value);
    }
    $this->getSession()->getPage()->fillField('Street address', 'Andrássy út 22');
    $this->submitForm([], 'Submit');

    $this->profileStorage->resetCache([$profile_address2->id()]);
    $profile_address2 = $this->profileStorage->load($profile_address2->id());

    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
    $address = $profile_address2->get('address')->first();

    $this->assertSession()->responseContains(new FormattableMarkup('Profile selected: :label', [':label' => $profile_address2->label()]));
    // Assert that field values have not changed.
    $this->assertEquals($this->address2['country_code'], $address->getCountryCode());
    $this->assertEquals($this->address2['given_name'], $address->getGivenName());
    $this->assertEquals($this->address2['family_name'], $address->getFamilyName());
    $this->assertEquals('Andrássy út 22', $address->getAddressLine1());
    $this->assertEquals($this->address2['locality'], $address->getLocality());
    $this->assertEquals($this->address2['postal_code'], $address->getPostalCode());
  }

}
