<?php

namespace Drupal\Tests\commerce_order\FunctionalJavascript;

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
  public function testProfileSelectAnonymous() {
    $this->useProfileForm(0, $this->address1);
  }

  /**
   * Tests the profile select form element for authenticated user.
   */
  public function testProfileSelectAuthenticated() {
    $account = $this->createUser();
    $this->drupalLogin($account);
    $this->useProfileForm($account->id(), $this->address1);
    // Create one more profile to test how the form behaves with two existing profiles.
    $this->useProfileForm($account->id(), $this->address2, 1);

    $this->drupalGet('/commerce_order_test/profile_select_form');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('profile_selection');
    // The last created profile should be selected by default.
    $this->assertSession()->pageTextContains($this->address2['locality']);

    $this->getSession()->getPage()->fillField('profile[profile_selection]', 1);
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextContains($this->address1['locality']);
    $this->submitForm([], 'Submit');
    $profile = \Drupal::entityTypeManager()->getStorage('profile')->load(1);
    // Assert that field values have not changed.
    foreach ($this->address1 as $key => $value) {
      $this->assertEquals($this->address1[$key], $profile->address->getValue()[0][$key], t('@key of address has not changed.', ['@key' => $key]));
    }

    // Edit a profile.
    $this->drupalGet('/commerce_order_test/profile_select_form');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('profile[profile_selection]');
    // The last created profile should be selected by default.
    $this->assertSession()->elementTextContains('css', '.locality', $this->address2['locality']);
    $this->assertSession()->fieldNotExists('profile[cancel]');

    $this->drupalPostForm(NULL, [], ['profile[edit]']);
    foreach ($this->address1 as $key => $value) {
      $this->assertSession()->fieldValueEquals('profile[address][0][address][' . $key . ']', $value);
    }
    $this->assertSession()->fieldExists('profile[cancel]');
    $this->assertSession()->fieldNotExists('profile[profile_selection]');
    $edit = [
      'profile[address][0][address][address_line1]' => 'Andrássy út 22',
    ];
    $this->submitForm([], 'Submit');
    $profile = \Drupal::entityTypeManager()->getStorage('profile')->resetCache([1]);
    $profile = \Drupal::entityTypeManager()->getStorage('profile')->load([1]);
    // Assert that only address_line1 has changed.
    foreach ($this->address1 as $key => $value) {
      if ($key == 'address_line1') {
        $this->assertEquals($edit['profile[address][0][address][address_line1]'], $profile->address->getValue()[0][$key], t('@key of address has changed.', ['@key' => $key]));
      }
      else {
        $this->assertEquals($this->address1[$key], $profile->address->getValue()[0][$key], t('@key of address has not changed.', ['@key' => $key]));
      }
    }
    $profile_ids = \Drupal::service('entity.query')
      ->get('profile')
      ->condition('uid', $account->id())
      ->execute();
    $this->Equals(2, count($profile_ids), t('No new profile has been created after editing an existing one.'));
  }

  /**
   * Submits the test profile select form.
   *
   * @param int $uid
   *   The user uid using the form.
   * @param array $address_fields
   *   An associative array of address fields to submit.
   * @param int $initial_profile_count
   *   The number of profiles before the form submission.
   */
  protected function useProfileForm($uid, array $address_fields, $initial_profile_count = 0) {
    $profile_ids = $this->profileStorage->getQuery()->count()->condition('uid', $uid)->execute();
    $this->assertEmpty($profile_ids);

    $this->drupalGet(Url::fromRoute('commerce_order_test.profile_select_form'));
    $this->assertSession()->statusCodeEquals(200);

    if (0 == $initial_profile_count || 0 == $uid) {
      $this->assertSession()->fieldNotExists('Select a profile');
    }
    else {
      $this->assertSession()->fieldExists('Select a profile');
      $this->getSession()->getPage()->fillField('Select a profile', 'new_profile');
      $this->waitForAjaxToFinish();
    }
    $this->getSession()->getPage()->fillField('Country', $address_fields['country_code']);
    $this->waitForAjaxToFinish();

    $this->createScreenshot();

    $edit = [];
    unset($address_fields['country_code']);
    foreach ($address_fields as $key => $value) {
      $edit['profile[address][0][address][' . $key . ']'] = $value;
    }

    $this->submitForm($edit, 'Submit');

    $profile_ids = $this->profileStorage->getQuery()->count()->condition('uid', $uid)->execute();
    $this->assertEquals($initial_profile_count + 1, count($profile_ids));
  }

}
