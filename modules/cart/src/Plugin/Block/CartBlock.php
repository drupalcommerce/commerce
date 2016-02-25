<?php

namespace Drupal\commerce_cart\Plugin\Block;

use Drupal\commerce_cart\CartProviderInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Entity\View;
use Drupal\Core\Cache\Cache;

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
      'item_text' => $this->t('items'),
      'dropdown' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['commerce_cart_item_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Items text'),
      '#default_value' => $this->configuration['item_text'],
      '#description' => $this->t('Shown after the number. Defaults to "items"'),
    ];
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
    $this->configuration['item_text'] = $form_state->getValue('commerce_cart_item_text');
    $this->configuration['dropdown'] = $form_state->getValue('commerce_cart_dropdown');
  }

  /**
   * Builds the cart block.
   *
   * @return array
   *   A render array.
   */
  public function build() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $carts */
    $carts = $this->cartProvider->getCarts();
    $carts = array_filter($carts, function ($cart) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
      return $cart->hasLineItems();
    });

    $count = 0;
    $cart_views = [];
    if (!empty($carts)) {
      $cart_views = $this->getCartViews($carts);
      foreach ($carts as $cart_id => $cart) {
        foreach ($cart->getLineItems() as $line_item) {
          $count += (int) $line_item->getQuantity();
        }
      }
    }

    $links = [];
    $links[] = [
      '#type' => 'link',
      '#title' => t('Cart'),
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
      '#item_text' => $this->configuration['item_text'],
      '#url' => Url::fromRoute('commerce_cart.page')->toString(),
      '#content' => $cart_views,
      '#links' => $links,
    ];
  }

  /**
   * Gets the cart views for each cart.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface[] $carts
   *   The cart orders.
   *
   * @return array
   *   An array of view ids keyed by cart order id.
   */
  protected function getCartViews(array $carts) {
    $cart_views = [];
    if ($this->configuration['dropdown']) {
      $order_type_ids = array_map(function($cart) {
        return $cart->bundle();
      }, $carts);
      $order_type_storage = $this->entityTypeManager->getStorage('commerce_order_type');
      $order_types = $order_type_storage->loadMultiple(array_unique($order_type_ids));

      $available_views = [];
      foreach ($order_type_ids as $cart_id => $order_type_id) {
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
  public function getCacheMaxAge() {
    $cache_max_age = 0;

    // Use the cart provider to get the carts.
    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $carts */
    $carts = $this->cartProvider->getCarts();
    $carts = array_filter($carts, function ($cart) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
      return $cart->hasLineItems();
    });

    $cart_views = [];
    if (!empty($carts)) {
      $cart_views = $this->getCartViews($carts);
    }
    foreach ($cart_views as $cart_view) {
      $view = View::load($cart_view['#name']);

      if ($view->getCacheMaxAge() == Cache::PERMANENT) {
        $cache_max_age = Cache::PERMANENT;
        break;
      }
      else if ($cache_max_age < $view->getCacheMaxAge()) {
        $cache_max_age = $view->getCacheMaxAge();
      }
    }

    return $cache_max_age;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = [];

    // Use the cart provider to get the carts.
    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $carts */
    $carts = $this->cartProvider->getCarts();
    $carts = array_filter($carts, function ($cart) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
      return $cart->hasLineItems();
    });

    $cart_views = [];
    if (!empty($carts)) {
      $cart_views = $this->getCartViews($carts);
    }
    foreach ($cart_views as $cart_view) {
      $view = View::load($cart_view['#name']);
      $cache_contexts = Cache::mergeContexts($view->getCacheContexts(), $cache_contexts);
    }

    return $cache_contexts;
  }

}
