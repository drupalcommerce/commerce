<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileType;
use Drupal\user\Entity\User;

/**
 * Tests the address book.
 *
 * @coversDefaultClass \Drupal\commerce_order\AddressBook
 *
 * @group commerce
 */
class AddressBookTest extends OrderKernelTestBase {

  /**
   * The address book.
   *
   * @var \Drupal\commerce_order\AddressBookInterface
   */
  protected $addressBook;

  /**
   * The test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The default profile.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $defaultProfile;

  /**
   * The order profile.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $orderProfile;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_order_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->addressBook = $this->container->get('commerce_order.address_book');
    $this->user = $this->createUser(['mail' => 'user1@example.com']);

    // Create a default profile for the current user.
    $this->defaultProfile = Profile::create([
      'type' => 'customer',
      'uid' => $this->user->id(),
      'address' => [
        'country_code' => 'US',
        'administrative_area' => 'CA',
        'locality' => 'Mountain View',
        'postal_code' => '94043',
        'address_line1' => '1098 Alta Ave',
        'organization' => 'Google Inc.',
        'given_name' => 'John',
        'family_name' => 'Smith',
      ],
    ]);
    $this->defaultProfile->save();
    $this->defaultProfile = $this->reloadEntity($this->defaultProfile);

    $this->orderProfile = Profile::create([
      'type' => 'customer',
      'uid' => 0,
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
      'data' => [
        'copy_to_address_book' => TRUE,
      ],
    ]);
    $this->orderProfile->save();
    $this->orderProfile = $this->reloadEntity($this->orderProfile);
  }

  /**
   * @covers ::hasUi
   */
  public function testHasUi() {
    // The address book UI is exposed by default.
    $this->assertTrue($this->addressBook->hasUi());

    $profile_type = ProfileType::load('customer');
    $profile_type->setMultiple(FALSE);
    $profile_type->save();
    // Confirm that there's no address book when there's only a single profile.
    $this->assertFalse($this->addressBook->hasUi());

    // Confirm that two single-profile types do get an address book.
    $bundle_entity_duplicator = $this->container->get('entity.bundle_entity_duplicator');
    $new_profile_type = $bundle_entity_duplicator->duplicate($profile_type, [
      'id' => 'shipping',
      'label' => 'Shipping',
    ]);
    $this->assertTrue($this->addressBook->hasUi());

    // Confirm that without any customer profile types, there is no UI exposed.
    $new_profile_type->delete();
    $profile_type->setThirdPartySetting('commerce_order', 'customer_profile_type', FALSE);
    $profile_type->save();
    $this->assertFalse($this->addressBook->hasUi());
  }

  /**
   * @covers ::loadTypes
   */
  public function testLoadProfileTypes() {
    $profile_types = $this->addressBook->loadTypes();
    $this->assertCount(1, $profile_types);
    $this->assertArrayHasKey('customer', $profile_types);

    $bundle_entity_duplicator = $this->container->get('entity.bundle_entity_duplicator');
    $customer_profile_type = ProfileType::load('customer');
    $bundle_entity_duplicator->duplicate($customer_profile_type, [
      'id' => 'shipping',
      'label' => 'Shipping',
    ]);
    $test_profile_type = ProfileType::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $test_profile_type->save();

    $profile_types = $this->addressBook->loadTypes();
    $this->assertCount(2, $profile_types);
    $this->assertArrayNotHasKey('test', $profile_types);
  }

  /**
   * @covers ::loadAll
   * @covers ::load
   */
  public function testLoadProfiles() {
    $second_profile = Profile::create([
      'type' => 'customer',
      'uid' => $this->user->id(),
      'address' => [
        'country_code' => 'RS',
        'postal_code' => '11000',
        'locality' => 'Belgrade',
        'address_line1' => 'Cetinjska 15',
        'given_name' => 'John',
        'family_name' => 'Smith',
      ],
    ]);
    $second_profile->save();
    $second_profile = $this->reloadEntity($second_profile);

    $this->assertEquals([3 => $second_profile, 1 => $this->defaultProfile], $this->addressBook->loadAll($this->user, 'customer'));
    $this->assertEquals([1 => $this->defaultProfile], $this->addressBook->loadAll($this->user, 'customer', ['US']));
    $this->assertEquals([3 => $second_profile], $this->addressBook->loadAll($this->user, 'customer', ['RS']));

    $this->assertEquals($this->defaultProfile, $this->addressBook->load($this->user, 'customer'));
    $this->assertNull($this->addressBook->load($this->user, 'customer', ['RS']));
  }

  /**
   * @covers ::needsCopy
   */
  public function testNeedsCopy() {
    $profile = Profile::create([
      'type' => 'customer',
    ]);
    $profile->save();
    $this->assertFalse($this->addressBook->needsCopy($profile));

    $profile = Profile::create([
      'type' => 'customer',
      'data' => [
        'copy_to_address_book' => TRUE,
      ],
    ]);
    $profile->save();
    $this->assertTrue($this->addressBook->needsCopy($profile));
  }

  /**
   * Test copying when multiple profiles are allowed per customer.
   *
   * @covers ::copy
   * @covers ::allowsMultiple
   */
  public function testCopyMultiple() {
    $order_address = array_filter($this->orderProfile->get('address')->first()->getValue());
    // Confirm that trying to copy to an anonymous user doesn't explode, or
    // create ghost profiles.
    $this->addressBook->copy($this->orderProfile, User::getAnonymousUser());
    $new_profile = Profile::load(3);
    $this->assertEmpty($new_profile);

    $this->addressBook->copy($this->orderProfile, $this->user);
    // Confirm that a new profile was created with the original field data.
    $new_profile = Profile::load(3);
    $this->assertNotEmpty($new_profile);
    $this->assertFalse($new_profile->isDefault());
    $this->assertEquals($this->user->id(), $new_profile->getOwnerId());
    $this->assertEquals($order_address, array_filter($new_profile->get('address')->first()->getValue()));
    $this->assertNull($new_profile->getData('copy_to_address_book'));
    // Confirm that the order profile was updated to point to the new profile.
    $this->orderProfile = $this->reloadEntity($this->orderProfile);
    $this->assertNull($this->orderProfile->getData('copy_to_address_book'));
    $this->assertEquals($new_profile->id(), $this->orderProfile->getData('address_book_profile_id'));

    // Confirm that copying the profile again updates the address book profile.
    $order_address = [
      'country_code' => 'US',
      'postal_code' => '53177',
      'locality' => 'Milwaukee',
      'address_line1' => 'Pabst Blue Ribbon Dr',
      'administrative_area' => 'WI',
      'given_name' => 'Frederick',
      'family_name' => 'Pabst Jr.',
    ];
    $this->orderProfile->set('address', $order_address);
    $this->orderProfile->save();
    $this->addressBook->copy($this->orderProfile, $this->user);
    $new_profile = $this->reloadEntity($new_profile);
    $this->assertEquals($order_address, array_filter($new_profile->get('address')->first()->getValue()));
    $non_expected_profile = Profile::load(4);
    $this->assertEmpty($non_expected_profile);
  }

  /**
   * Test copying when a single profile is allowed per customer.
   *
   * @covers ::copy
   * @covers ::allowsMultiple
   */
  public function testCopySingle() {
    $order_address = array_filter($this->orderProfile->get('address')->first()->getValue());
    /** @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
    $profile_type = ProfileType::load('customer');
    $profile_type->setMultiple(FALSE);
    $profile_type->save();

    // Confirm that the default profile was updated.
    $this->addressBook->copy($this->orderProfile, $this->user);
    $new_profile = Profile::load(3);
    $this->assertEmpty($new_profile);
    $this->defaultProfile = $this->reloadEntity($this->defaultProfile);
    $this->assertEquals($order_address, array_filter($this->defaultProfile->get('address')->first()->getValue()));
    $this->assertNull($this->defaultProfile->getData('copy_to_address_book'));
    // Confirm that the order profile now points to the default profile.
    $this->orderProfile = $this->reloadEntity($this->orderProfile);
    $this->assertEquals($this->defaultProfile->id(), $this->orderProfile->getData('address_book_profile_id'));

    // Confirm that a default profile will be created, if missing.
    $this->defaultProfile->delete();
    $this->addressBook->copy($this->orderProfile, $this->user);
    $new_default_profile = Profile::load(3);
    $this->assertNotEmpty($new_default_profile);
    $this->assertTrue($new_default_profile->isDefault());
    $this->assertEquals($this->user->id(), $new_default_profile->getOwnerId());
    $this->assertEquals($order_address, array_filter($new_default_profile->get('address')->first()->getValue()));
    $this->assertNull($new_default_profile->getData('copy_to_address_book'));
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $this->orderProfile = $this->reloadEntity($this->orderProfile);
    $this->assertEquals($new_default_profile->id(), $this->orderProfile->getData('address_book_profile_id'));
  }

}
