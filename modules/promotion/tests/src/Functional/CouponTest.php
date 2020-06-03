<?php

namespace Drupal\Tests\commerce_promotion\Functional;

use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the admin UI for coupons.
 *
 * @group commerce
 */
class CouponTest extends CommerceBrowserTestBase {

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
      'bulk generate commerce_promotion_coupon',
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
          'percentage' => '0.10',
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
    $coupon_count = $this->getSession()->getPage()->findAll('xpath', '//table/tbody/tr/td[text()="' . $code . '"]');
    $this->assertEquals(count($coupon_count), 1, 'Coupon exists in the table.');

    $coupon = Coupon::load(1);
    $this->assertEquals($this->promotion->id(), $coupon->getPromotionId());
    $this->assertEquals($code, $coupon->getCode());

    $this->container->get('entity_type.manager')->getStorage('commerce_promotion')->resetCache([$this->promotion->id()]);
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

    $this->container->get('entity_type.manager')->getStorage('commerce_promotion_coupon')->resetCache([$coupon->id()]);
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
      'usage_limit_customer' => 0,
    ]);
    $this->drupalGet($coupon->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], t('Delete'));

    $this->container->get('entity_type.manager')->getStorage('commerce_promotion_coupon')->resetCache([$coupon->id()]);
    $coupon_exists = (bool) Coupon::load($coupon->id());
    $this->assertFalse($coupon_exists);
  }

  /**
   * Tests bulk generation of coupons.
   */
  public function testGenerateCoupons() {
    // Generate 52 single-use coupons.
    $coupon_quantity = 52;
    $this->drupalGet('/promotion/' . $this->promotion->id() . '/coupons');
    $this->getSession()->getPage()->clickLink('Generate coupons');
    $this->getSession()->getPage()->selectFieldOption('format', 'numeric');
    $this->getSession()->getPage()->fillField('quantity', (string) $coupon_quantity);
    $this->getSession()->getPage()->pressButton('Generate');
    $this->checkForMetaRefresh();

    $this->assertSession()->pageTextContains("Generated $coupon_quantity coupons.");
    $coupon_count = $this->getSession()->getPage()->findAll('xpath', '//table/tbody/tr/td[text()="0 / 1"]');
    $this->assertEquals(count($coupon_count), $coupon_quantity);

    $coupons = Coupon::loadMultiple();
    $this->assertEquals(count($coupons), $coupon_quantity);
    foreach ($coupons as $id => $coupon) {
      $this->assertEquals($this->promotion->id(), $coupon->getPromotionId());
      $this->assertTrue(ctype_digit($coupon->getCode()));
      $this->assertEquals(strlen($coupon->getCode()), 8);
      $this->assertEquals(1, $coupon->getUsageLimit());
    }
    $this->container->get('entity_type.manager')->getStorage('commerce_promotion')->resetCache([$this->promotion->id()]);
    $this->promotion = Promotion::load($this->promotion->id());
    foreach ($coupons as $id => $coupon) {
      $this->assertTrue($this->promotion->hasCoupon($coupon));
    }

    // Generate 6 unlimited-use coupons.
    $coupon_quantity = 6;
    $this->drupalGet('/promotion/' . $this->promotion->id() . '/coupons');
    $this->getSession()->getPage()->clickLink('Generate coupons');
    $this->getSession()->getPage()->selectFieldOption('format', 'numeric');
    $this->getSession()->getPage()->fillField('quantity', (string) $coupon_quantity);
    $this->getSession()->getPage()->selectFieldOption('limit', 0);
    $this->getSession()->getPage()->pressButton('Generate');
    $this->checkForMetaRefresh();

    $this->assertSession()->pageTextContains("Generated $coupon_quantity coupons.");
    $coupon_count = $this->getSession()->getPage()->findAll('xpath', '//table/tbody/tr/td[text()="0 / Unlimited"]');
    $this->assertEquals(count($coupon_count), $coupon_quantity);
  }

}
