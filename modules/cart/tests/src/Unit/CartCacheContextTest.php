<?php

namespace Drupal\Tests\commerce_cart\Unit;

use Drupal\commerce_cart\Cache\Context\CartCacheContext;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\Core\Render\TestCacheableDependency;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_cart\Cache\Context\CartCacheContext
 * @group commerce
 */
class CartCacheContextTest extends UnitTestCase {

  /**
   * Tests commerce 'cart' cache context.
   */
  public function testCartCacheContext() {
    $account = $this->createMock(AccountInterface::class);
    $cartProvider = $this->createMock(CartProviderInterface::class);
    $cartProvider->expects($this->once())->method('getCartIds')->willReturn(['23', '34']);
    $cartProvider->expects($this->once())->method('getCarts')->willReturn([
      new TestCacheableDependency([], ['commerce_cart:23'], 0),
      new TestCacheableDependency([], ['commerce_cart:24'], 0),
    ]);

    $cartCache = new CartCacheContext($account, $cartProvider);
    $this->assertEquals('23:34', $cartCache->getContext());
    $this->assertEquals(['commerce_cart:23', 'commerce_cart:24'], $cartCache->getCacheableMetadata()->getCacheTags());
  }

}
