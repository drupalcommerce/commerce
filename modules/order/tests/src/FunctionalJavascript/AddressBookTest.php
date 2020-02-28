<?php

namespace Drupal\Tests\commerce_order\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileType;

/**
 * Tests the address book pages.
 *
 * @group commerce
 */
class AddressBookTest extends OrderWebDriverTestBase {

  /**
   * The first test address.
   *
   * @var array
   */
  protected $firstAddress = [
    'country_code' => 'US',
    'administrative_area' => 'SC',
    'locality' => 'Greenville',
    'postal_code' => '29616',
    'address_line1' => '9 Drupal Ave',
    'given_name' => 'Bryan',
    'family_name' => 'Centarro',
  ];

  /**
   * The second test address.
   *
   * @var array
   */
  protected $secondAddress = [
    'country_code' => 'US',
    'administrative_area' => 'CA',
    'locality' => 'Mountain View',
    'postal_code' => '94043',
    'address_line1' => '1098 Alta Ave',
    'organization' => 'Google Inc.',
    'given_name' => 'John',
    'family_name' => 'Smith',
  ];

  /**
   * The third test address.
   *
   * @var array
   */
  protected $thirdAddress = [
    'country_code' => 'US',
    'postal_code' => '53177',
    'locality' => 'Milwaukee',
    'address_line1' => 'Pabst Blue Ribbon Dr',
    'administrative_area' => 'WI',
    'given_name' => 'Frederick',
    'family_name' => 'Pabst',
  ];

  /**
   * The fourth test address.
   *
   * @var array
   */
  protected $fourthAddress = [
    'country_code' => 'FR',
    'locality' => 'Paris',
    'postal_code' => '75002',
    'address_line1' => '38 Rue du Sentier',
    'given_name' => 'Leon',
    'family_name' => 'Blum',
  ];

  /**
   * Gets the permissions for the admin user.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getAdministratorPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'access commerce administration pages',
      'access user profiles',
      'administer commerce_currency',
      'administer commerce_store',
      'administer commerce_store_type',
      'administer profile',
      'administer profile types',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Log out of the admin user.
    $this->drupalLogout();
  }

  /**
   * Tests the overview access checking.
   */
  public function testOverviewAccess() {
    // Confirm that the anonymous user doesn't have an address book.
    $this->drupalGet('user/0/address-book');
    $this->drupalGet(Url::fromRoute('commerce_order.address_book.overview', [
      'user' => 0,
    ]));
    $this->assertSession()->pageTextContains('Access Denied');

    // Confirm that no address book is available when the user can't view
    // the user's canonical page ("/user/{user}").
    $customer = $this->createUser(['view any customer profile']);
    $this->drupalLogin($customer);
    $this->drupalGet($this->adminUser->toUrl('canonical'));
    $this->assertSession()->pageTextContains('Access denied');
    $this->drupalGet(Url::fromRoute('commerce_order.address_book.overview', [
      'user' => $this->adminUser->id(),
    ]));
    $this->assertSession()->pageTextContains('Access Denied');

    // Confirm that no address book is available when the user can't view
    // any profile types.
    $customer = $this->createUser(['access user profiles']);
    $this->drupalLogin($customer);
    $this->drupalGet($this->adminUser->toUrl('canonical'));
    $this->assertSession()->pageTextNotContains('Access denied');
    $this->drupalGet(Url::fromRoute('commerce_order.address_book.overview', [
      'user' => $this->adminUser->id(),
    ]));
    $this->assertSession()->pageTextContains('Access Denied');

    // Confirm that the address book is visible when the user can view
    // at least one profile type.
    $customer = $this->createUser([
      'access user profiles',
      'view any customer profile',
    ]);
    $this->createEntity('profile', [
      'type' => 'customer',
      'uid' => $this->adminUser->id(),
      'address' => $this->secondAddress,
    ]);
    $this->drupalLogin($customer);
    $this->drupalGet($this->adminUser->toUrl('canonical'));
    $this->assertSession()->pageTextNotContains('Access denied');
    $this->drupalGet(Url::fromRoute('commerce_order.address_book.overview', [
      'user' => $this->adminUser->id(),
    ]));
    $this->assertSession()->pageTextNotContains('Access Denied');
    $this->assertSession()->pageTextContains('1098 Alta Ave');
    // Confirm that the local task is present.
    $this->assertSession()->linkExists('Address book');
    $this->assertSession()->linkNotExists('Billing information');
  }

  /**
   * Tests the add form access checking.
   */
  public function testCreateAccess() {
    $first_user = $this->createUser(['view own customer profile']);
    $second_user = $this->createUser([
      'create customer profile',
      'view any profile',
      'access user profiles',
    ]);
    $third_user = $this->createUser([
      'administer profile',
      'access user profiles',
    ]);

    $this->createEntity('profile', [
      'type' => 'customer',
      'uid' => $first_user->id(),
      'address' => $this->firstAddress,
      'status' => TRUE,
    ]);

    $overview_url = Url::fromRoute('commerce_order.address_book.overview', [
      'user' => $first_user->id(),
    ]);
    // Confirm that the user with only "view" permissions can see
    // the overview page, but not the "add" page.
    $this->drupalLogin($first_user);
    $this->drupalGet($overview_url);
    $this->assertSession()->pageTextNotContains('Access Denied');
    $this->assertSession()->pageTextContains('9 Drupal Ave');
    $this->assertSession()->linkNotExists('Add address');

    $add_url = Url::fromRoute('commerce_order.address_book.add_form', [
      'user' => $first_user->id(),
      'profile_type' => 'customer',
    ]);
    $this->drupalGet($add_url);
    $this->assertSession()->pageTextContains('Access denied');

    // Confirm that the second user can't add a profile for the first user.
    $this->drupalLogin($second_user);
    $this->drupalGet($overview_url);
    $this->assertSession()->pageTextNotContains('Access Denied');
    $this->assertSession()->pageTextContains('9 Drupal Ave');
    $this->assertSession()->linkNotExists('Add address');

    $this->drupalGet($add_url);
    $this->assertSession()->pageTextContains('Access denied');

    // Confirm that the third user can add a profile for the first user.
    $this->drupalLogin($third_user);
    $this->drupalGet($overview_url);
    $this->assertSession()->pageTextContains('9 Drupal Ave');
    $this->assertSession()->linkExists('Add address');
    $this->getSession()->getPage()->clickLink('Add address');
    $this->getSession()->getPage()->fillField('address[0][address][country_code]', 'FR');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->fourthAddress as $property => $value) {
      $this->getSession()->getPage()->fillField("address[0][address][$property]", $value);
    }
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Saved the 38 Rue du Sentier address.');
    $profile = Profile::load('2');
    $this->assertNotEmpty($profile);
    $this->assertEquals('38 Rue du Sentier', $profile->get('address')->address_line1);
    $this->assertEquals($first_user->id(), $profile->getOwnerId());

    $this->drupalGet($add_url);
    $this->assertSession()->pageTextNotContains('Access denied');
    // Confirm that no further profiles can be added if the profile type
    // only allows a single profile per user.
    $profile = ProfileType::load('customer');
    $profile->setMultiple(FALSE);
    $profile->save();
    $this->drupalGet($add_url);
    $this->assertSession()->pageTextContains('Access denied');
  }

  /**
   * Tests the fallback to the default profile UI.
   *
   * When there's only one profile type, and it only allows one profile per
   * customer, the address book should not be available, and profile module's
   * default UI should be used instead.
   */
  public function testFallback() {
    $profile = ProfileType::load('customer');
    $profile->setMultiple(FALSE);
    $profile->save();
    \Drupal::service('router.builder')->rebuild();

    $customer = $this->createUser([
      'access user profiles',
      'view own customer profile',
      'update own customer profile',
    ]);
    $profile = $this->createEntity('profile', [
      'type' => 'customer',
      'uid' => $customer->id(),
      'address' => $this->firstAddress,
    ]);
    $this->drupalLogin($customer);
    $this->drupalGet(Url::fromRoute('commerce_order.address_book.overview', [
      'user' => $customer->id(),
    ]));
    $this->assertSession()->pageTextContains('Access Denied');
    $this->drupalGet($customer->toUrl());
    $this->assertSession()->linkNotExists('Address book');
    // The local task provided by profile module should be visible.
    $this->assertSession()->linkExists('Customer information');
    $this->getSession()->getPage()->clickLink('Customer information');
    $this->saveHtmlOutput();

    // Confirm that the profile can be updated.
    foreach ($this->firstAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("address[0][address][$property]", $value);
    }
    $this->submitForm([
      'address[0][address][address_line1]' => '10 Drupal Ave',
    ], 'Save');
    $this->assertSession()->pageTextContains('The profile has been saved.');
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->reloadEntity($profile);
    $this->assertEquals('10 Drupal Ave', $profile->get('address')->address_line1);
  }

  /**
   * Tests the address book overview page with the default profile type.
   */
  public function testDefaultOverview() {
    $customer = $this->createUser([
      'access user profiles',
      'create customer profile',
      'update own customer profile',
      'delete own customer profile',
      'view own customer profile',
      'administer profile',
    ]);
    $this->drupalLogin($customer);
    $this->drupalGet(Url::fromRoute('commerce_order.address_book.overview', [
      'user' => $customer->id(),
    ]));
    $this->assertSession()->pageTextContains('There are no addresses yet.');

    // Confirm that a profile can be created.
    $this->getSession()->getPage()->clickLink('Add address');
    $this->getSession()->getPage()->fillField('address[0][address][country_code]', 'FR');
    $this->assertSession()->assertWaitOnAjaxRequest();
    foreach ($this->fourthAddress as $property => $value) {
      $this->getSession()->getPage()->fillField("address[0][address][$property]", $value);
    }
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Saved the 38 Rue du Sentier address.');
    $rendered_address = $this->getSession()->getPage()->find('css', 'p.address');
    $this->assertNotEmpty($rendered_address);
    $this->assertContains('38 Rue du Sentier', $rendered_address->getText());

    // Confirm that a profile can be edited.
    $this->getSession()->getPage()->clickLink('Edit');
    foreach ($this->fourthAddress as $property => $value) {
      $this->assertSession()->fieldValueEquals("address[0][address][$property]", $value);
    }
    $this->submitForm([
      'address[0][address][address_line1]' => '39 Rue du Sentier',
    ], 'Save');
    $this->assertSession()->pageTextContains('Saved the 39 Rue du Sentier address.');
    $rendered_address = $this->getSession()->getPage()->find('css', 'p.address');
    $this->assertNotEmpty($rendered_address);
    $this->assertContains('39 Rue du Sentier', $rendered_address->getText());

    // Confirm that a profile can be set as default.
    /** @var \Drupal\profile\Entity\ProfileInterface $second_profile */
    $second_profile = $this->createEntity('profile', [
      'type' => 'customer',
      'uid' => $customer->id(),
      'address' => $this->secondAddress,
    ]);
    $this->assertFalse($second_profile->isDefault());
    $this->drupalGet(Url::fromRoute('commerce_order.address_book.overview', [
      'user' => $customer->id(),
    ]));
    $this->assertSession()->pageTextContains('39 Rue du Sentier');
    $this->assertSession()->pageTextContains($this->secondAddress['address_line1']);

    $set_default_links = $this->getSession()->getPage()->findAll('css', '.address-book__set-default-link');
    $this->assertCount(1, $set_default_links);
    $set_default_link = reset($set_default_links);
    $set_default_link->click();
    $this->assertSession()->pageTextContains($this->secondAddress['address_line1'] . ' is now the default address.');

    $set_default_links = $this->getSession()->getPage()->findAll('css', '.address-book__set-default-link');
    $this->assertCount(1, $set_default_links);
    $set_default_link = reset($set_default_links);
    $set_default_link->click();
    $this->assertSession()->pageTextContains('39 Rue du Sentier is now the default address.');

    // Confirm that a profile can be deleted.
    $delete_links = $this->getSession()->getPage()->findAll('css', '.address-book__delete-link');
    $this->assertCount(2, $delete_links);
    $delete_link = reset($delete_links);
    $delete_link->click();
    $this->assertSession()->pageTextContains('Are you sure you want to delete the 39 Rue du Sentier address?');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains('The 39 Rue du Sentier address has been deleted.');
  }

  /**
   * Tests the address book overview page with several profile types.
   */
  public function testExtendedOverview() {
    $customer_profile_type = ProfileType::load('customer');
    $bundle_entity_duplicator = $this->container->get('entity.bundle_entity_duplicator');
    $bundle_entity_duplicator->duplicate($customer_profile_type, [
      'id' => 'customer_shipping',
      'label' => 'Customer (Shipping)',
      'display_label' => 'Shipping information',
      'multiple' => TRUE,
    ]);
    $customer_profile_type->setDisplayLabel('Billing information');
    $customer_profile_type->setMultiple(FALSE);
    $customer_profile_type->save();

    $customer_profile_type = ProfileType::load('customer');
    $bundle_entity_duplicator = $this->container->get('entity.bundle_entity_duplicator');
    $bundle_entity_duplicator->duplicate($customer_profile_type, [
      'id' => 'customer_test',
      'label' => 'Customer (Test)',
      'display_label' => 'Test information',
    ]);

    $customer = $this->createUser([
      'access user profiles',
      'create customer profile',
      'update own customer profile',
      'delete own customer profile',
      'delete own customer_shipping profile',
      'view own customer profile',
      'view own customer_shipping profile',
    ]);
    $this->drupalLogin($customer);
    $this->drupalGet(Url::fromRoute('commerce_order.address_book.overview', [
      'user' => $customer->id(),
    ]));

    $this->assertSession()->pageTextContains('Billing information');
    // Confirm that there is no empty text for billing information, because
    // there is an add link.
    $container = $this->getSession()->getPage()->find('css', '.address-book__container--customer');
    $this->assertNotContains('There are no addresses yet.', $container->getText());
    $add_link = $this->getSession()->getPage()->find('css', '.address-book__container--customer .address-book__add-link');
    $this->assertNotEmpty($add_link);
    $add_link->click();
    $this->assertSession()->fieldExists('address[0][address][address_line1]');

    // Confirm that there is only an edit link after a profile has been created,
    // since the profile type doesn't allow multiple profiles per customer.
    $billing_profile = $this->createEntity('profile', [
      'type' => 'customer',
      'uid' => $customer->id(),
      'address' => $this->firstAddress,
    ]);
    $this->drupalGet(Url::fromRoute('commerce_order.address_book.overview', [
      'user' => $customer->id(),
    ]));
    $this->assertSession()->elementNotExists('css', '.address-book__container--customer .address-book__add-link');
    $this->assertSession()->elementExists('css', '.address-book__container--customer .address-book__edit-link');
    $this->assertSession()->elementNotExists('css', '.address-book__container--customer .address-book__delete-link');
    $this->assertSession()->elementNotExists('css', '.address-book__container--customer .address-book__set-default-link');

    // Confirm that the add form isn't available directly either.
    $this->drupalGet(Url::fromRoute('commerce_order.address_book.add_form', [
      'user' => $customer->id(),
      'profile_type' => 'customer',
    ]));
    $this->assertSession()->pageTextContains('Access Denied');

    $this->drupalGet(Url::fromRoute('commerce_order.address_book.overview', [
      'user' => $customer->id(),
    ]));
    $this->assertSession()->pageTextContains('Shipping information');
    // Confirm that there is empty text for shipping information, because
    // there is no add link (due to lack of access).
    $add_link = $this->getSession()->getPage()->find('css', '.address-book__container--customer_shipping .address-book__add-link');
    $this->assertEmpty($add_link);
    $container = $this->getSession()->getPage()->find('css', '.address-book__container--customer_shipping');
    $this->assertContains('There are no addresses yet.', $container->getText());

    $this->createEntity('profile', [
      'type' => 'customer_shipping',
      'uid' => $customer->id(),
      'address' => $this->secondAddress,
    ]);
    $this->createEntity('profile', [
      'type' => 'customer_shipping',
      'uid' => $customer->id(),
      'address' => $this->thirdAddress,
    ]);
    $this->drupalGet(Url::fromRoute('commerce_order.address_book.overview', [
      'user' => $customer->id(),
    ]));
    // Confirm that the empty text is gone.
    $container = $this->getSession()->getPage()->find('css', '.address-book__container--customer_shipping');
    $this->assertNotContains('There are no addresses yet.', $container->getText());
    // Confirm that there are no edit/set default links, due to lack of access.
    $edit_links = $this->getSession()->getPage()->findAll('css', '.address-book__container--customer_shipping .address-book__edit-link');
    $this->assertEmpty($edit_links);
    $set_default_links = $this->getSession()->getPage()->findAll('css', '.address-book__container--customer_shipping .address-book__set-default-link');
    $this->assertEmpty($set_default_links);
    $delete_links = $this->getSession()->getPage()->findAll('css', '.address-book__container--customer_shipping .address-book__delete-link');
    $this->assertNotEmpty($delete_links);

    // Confirm that the profile types are filtered by access, and that
    // the customer_test profile type is not displayed.
    $this->createEntity('profile', [
      'type' => 'customer_test',
      'uid' => $customer->id(),
      'address' => $this->fourthAddress,
    ]);
    $this->assertSession()->pageTextNotContains('Test information');
    $this->assertSession()->pageTextNotContains($this->fourthAddress['address_line1']);
  }

}
