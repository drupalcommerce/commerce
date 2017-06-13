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
  public function testProfileSelectAuthenticatedEdit() {
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

    // Edit a profile.
    $this->drupalGet(Url::fromRoute('commerce_order_test.profile_select_form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('Select a profile');
    // The last created profile should be selected by default.
    $this->assertSession()->elementTextContains('css', '.locality', $this->address2['locality']);
    $this->assertSession()->fieldNotExists('profile[cancel]');

    $this->submitForm([], 'profile[edit]');
    foreach ($this->address1 as $key => $value) {
      $this->assertSession()->fieldValueEquals('profile[address][0][address][' . $key . ']', $value);
    }
    $this->assertSession()->fieldExists('profile[cancel]');
    $this->assertSession()->fieldNotExists('profile[profile_selection]');
    $edit = [
      'profile[address][0][address][address_line1]' => 'Andrássy út 22',
    ];
    $this->submitForm([], 'Submit');
    $this->profileStorage->resetCache([1]);
    $profile = $this->profileStorage->load([1]);
    // Assert that only address_line1 has changed.
    foreach ($this->address1 as $key => $value) {
      if ($key == 'address_line1') {
        $this->assertEquals($edit['profile[address][0][address][address_line1]'], $profile->address->getValue()[0][$key], t('@key of address has changed.', ['@key' => $key]));
      }
      else {
        $this->assertEquals($this->address1[$key], $profile->address->getValue()[0][$key], t('@key of address has not changed.', ['@key' => $key]));
      }
    }
    $profile_ids = $this->profileStorage->getQuery()->count()->condition('uid', $account->id())->execute();
    $this->assertEquals(2, $profile_ids, t('No new profile has been created after editing an existing one.'));
  }

}
