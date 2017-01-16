<?php

namespace Drupal\commerce_cart\Plugin\Block;

use Drupal\commerce_cart\CartProviderInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a cart block.
 *
 * @Block(
 *   id = "commerce_cart",
 *   admin_label = @Translation("Cart"),
 *   category = @Translation("Commerce")
 * )
 */
class CartBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CartBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CartProviderInterface $cart_provider, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->cartProvider = $cart_provider;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_cart.cart_provider'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'dropdown' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['commerce_cart_dropdown'] = [
      '#type' => 'radios',
      '#title' => $this->t('Display cart contents in a dropdown'),
      '#default_value' => (int) $this->configuration['dropdown'],
      '#options' => [
        $this->t('No'),
        $this->t('Yes'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['dropdown'] = $form_state->getValue('commerce_cart_dropdown');
  }

  /**
   * Builds the cart block.
   *
   * @return array
   *   A render array.
   */
  public function build() {
    $cachable_metadata = new CacheableMetadata();
    $cachable_metadata->addCacheContexts(['user', 'session']);

    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $carts */
    $carts = $this->cartProvider->getCarts();
    $carts = array_filter($carts, function ($cart) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
      // There is a chance the cart may have converted from a draft order, but
      // is still in session. Such as just completing check out. So we verify
      // that the cart is still a cart.
      return $cart->hasItems() && $cart->cart->value;
    });

    $count = 0;
    $cart_views = [];
    if (!empty($carts)) {
      $cart_views = $this->getCartViews($carts);
      foreach ($carts as $cart_id => $cart) {
        foreach ($cart->getItems() as $order_item) {
          $count += (int) $order_item->getQuantity();
        }
        $cachable_metadata->addCacheableDependency($cart);
      }
    }

    $links = [];
    $links[] = [
      '#type' => 'link',
      '#title' => $this->t('Cart'),
      '#url' => Url::fromRoute('commerce_cart.page'),
    ];

    return [
      '#attached' => [
        'library' => ['commerce_cart/cart_block'],
      ],
      '#theme' => 'commerce_cart_block',
      '#icon' => [
        '#theme' => 'image',
        '#uri' => drupal_get_path('module', 'commerce') . '/icons/ffffff/cart.png',
        '#alt' => $this->t('Shopping cart'),
      ],
      '#count' => $count,
      '#count_text' => $this->formatPlural($count, '@count item', '@count items'),
      '#url' => Url::fromRoute('commerce_cart.page')->toString(),
      '#content' => $cart_views,
      '#links' => $links,
      '#cache' => [
        'contexts' => ['cart'],
      ],
    ];
  }

  /**
   * Gets the cart views for each cart.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface[] $carts
   *   The cart orders.
   *
   * @return array
   *   An array of view ids keyed by cart order ID.
   */
  protected function getCartViews(array $carts) {
    $cart_views = [];
    if ($this->configuration['dropdown']) {
      $order_type_ids = array_map(function ($cart) {
        return $cart->bundle();
      }, $carts);
      $order_type_storage = $this->entityTypeManager->getStorage('commerce_order_type');
      $order_types = $order_type_storage->loadMultiple(array_unique($order_type_ids));

      $available_views = [];
      foreach ($order_type_ids as $cart_id => $order_type_id) {
        /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
        $order_type = $order_types[$order_type_id];
        $available_views[$cart_id] = $order_type->getThirdPartySetting('commerce_cart', 'cart_block_view', 'commerce_cart_block');
      }

      foreach ($carts as $cart_id => $cart) {
        $cart_views[] = [
          '#prefix' => '<div class="cart cart-block">',
          '#suffix' => '</div>',
          '#type' => 'view',
          '#name' => $available_views[$cart_id],
          '#arguments' => [$cart_id],
          '#embed' => TRUE,
        ];
      }
    }
    return $cart_views;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['cart']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    $cart_cache_tags = [];

    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $carts */
    $carts = $this->cartProvider->getCarts();
    foreach ($carts as $cart) {
      // Add tags for all carts regardless items or cart flag.
      $cart_cache_tags = Cache::mergeTags($cart_cache_tags, $cart->getCacheTags());
    }
    return Cache::mergeTags($cache_tags, $cart_cache_tags);
  }

}
