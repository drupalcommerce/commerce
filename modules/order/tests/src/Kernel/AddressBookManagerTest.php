<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileType;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the address book manager.
 *
 * @coversDefaultClass \Drupal\commerce_order\AddressBookManager
 *
 * @group commerce
 */
class AddressBookManagerTest extends CommerceKernelTestBase {

  /**
   * The address book manager.
   *
   * @var \Drupal\commerce_order\AddressBookManagerInterface
   */
  protected $addressBookManager;

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
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_order_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installConfig('commerce_order');

    $this->addressBookManager = $this->container->get('commerce_order.address_book_manager');
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
  }

  /**
   * @covers ::needsCopy
   */
  public function testNeedsCopy() {
    $profile = Profile::create([
      'type' => 'customer',
    ]);
    $profile->save();
    $this->assertFalse($this->addressBookManager->needsCopy($profile));

    $profile = Profile::create([
      'type' => 'customer',
      'data' => [
        'copy_to_address_book' => TRUE,
      ],
    ]);
    $profile->save();
    $this->assertTrue($this->addressBookManager->needsCopy($profile));
  }

  /**
   * Test copying when multiple profiles are allowed per customer.
   *
   * @covers ::copy
   */
  public function testCopyMultiple() {
    $order_address = array_filter($this->orderProfile->get('address')->first()->getValue());
    // Confirm that trying to copy to an anonymous user doesn't explode, or
    // create ghost profiles.
    $this->addressBookManager->copy($this->orderProfile, User::getAnonymousUser());
    $new_profile = Profile::load(3);
    $this->assertEmpty($new_profile);

    $this->addressBookManager->copy($this->orderProfile, $this->user);
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
  }

  /**
   * Test copying when a single profile is allowed per customer.
   *
   * @covers ::copy
   */
  public function testCopySingle() {
    $order_address = array_filter($this->orderProfile->get('address')->first()->getValue());
    /** @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
    $profile_type = ProfileType::load('customer');
    $profile_type->setMultiple(FALSE);
    $profile_type->save();
    \Drupal::service('entity_type.bundle.info')->clearCachedBundles();

    // Confirm that the default profile was updated.
    $this->addressBookManager->copy($this->orderProfile, $this->user);
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
    $this->addressBookManager->copy($this->orderProfile, $this->user);
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
