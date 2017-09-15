<?php

namespace Drupal\Tests\commerce_promotion\FunctionalJavascript;

use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;

/**
 * Tests the admin UI for coupons.
 *
 * @group commerce
 */
class CouponTest extends CommerceBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * The test promotion.
   *
   * @var \Drupal\commerce_promotion\Entity\PromotionInterface
   */
  protected $promotion;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'path',
    'commerce_product',
    'commerce_promotion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_promotion',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->promotion = $this->createEntity('commerce_promotion', [
      'name' => 'Promotion test',
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'offer' => [
        'target_plugin_id' => 'order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'start_date' => '2017-01-01',
      'status' => TRUE,
    ]);
  }

  /**
   * Tests creating a coupon.
   */
  public function testCreateCoupon() {
    $this->drupalGet('/promotion/' . $this->promotion->id() . '/coupons');
    $this->getSession()->getPage()->clickLink('Add coupon');

    // Check the integrity of the form.
    $this->assertSession()->fieldExists('code[0][value]');
    $code = $this->randomMachineName(8);
    $this->getSession()->getPage()->fillField('code[0][value]', $code);
    $this->submitForm([], t('Save'));
    $this->assertSession()->pageTextContains("Saved the $code coupon.");
    $coupon_count = $this->getSession()->getPage()->find('xpath', '//table/tbody/tr/td[text()="' . $code . '"]');
    $this->assertEquals(count($coupon_count), 1, 'Coupon exists in the table.');

    $coupon = Coupon::load(1);
    $this->assertEquals($this->promotion->id(), $coupon->getPromotionId());
    $this->assertEquals($code, $coupon->getCode());

    \Drupal::service('entity_type.manager')->getStorage('commerce_promotion')->resetCache([$this->promotion->id()]);
    $this->promotion = Promotion::load($this->promotion->id());
    $this->assertTrue($this->promotion->hasCoupon($coupon));
  }

  /**
   * Tests editing a coupon.
   */
  public function testEditCoupon() {
    $coupon = $this->createEntity('commerce_promotion_coupon', [
      'promotion_id' => $this->promotion->id(),
      'code' => $this->randomMachineName(8),
      'status' => TRUE,
    ]);

    $this->drupalGet($coupon->toUrl('edit-form'));
    $new_code = $this->randomMachineName(8);
    $edit = [
      'code[0][value]' => $new_code,
    ];
    $this->submitForm($edit, 'Save');

    \Drupal::service('entity_type.manager')->getStorage('commerce_promotion_coupon')->resetCache([$coupon->id()]);
    $coupon = Coupon::load($coupon->id());
    $this->assertEquals($new_code, $coupon->getCode());
  }

  /**
   * Tests deleting a coupon.
   */
  public function testDeleteCoupon() {
    $coupon = $this->createEntity('commerce_promotion_coupon', [
      'promotion_id' => $this->promotion->id(),
      'code' => $this->randomMachineName(8),
      'status' => FALSE,
      'usage_limit' => 0,
    ]);
    $this->drupalGet($coupon->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], t('Delete'));

    \Drupal::service('entity_type.manager')->getStorage('commerce_promotion_coupon')->resetCache([$coupon->id()]);
    $coupon_exists = (bool) Coupon::load($coupon->id());
    $this->assertFalse($coupon_exists);
  }

}
