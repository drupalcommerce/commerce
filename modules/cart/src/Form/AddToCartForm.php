<?php

namespace Drupal\commerce_cart\Form;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Resolver\OrderTypeResolverInterface;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\commerce_store\StoreContextInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the order item add to cart form.
 */
class AddToCartForm extends ContentEntityForm {

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The order type resolver.
   *
   * @var \Drupal\commerce_order\Resolver\OrderTypeResolverInterface
   */
  protected $orderTypeResolver;

  /**
   * The store context.
   *
   * @var \Drupal\commerce_store\StoreContextInterface
   */
  protected $storeContext;

  /**
   * The chain base price resolver.
   *
   * @var \Drupal\commerce_price\Resolver\ChainPriceResolverInterface
   */
  protected $chainPriceResolver;

  /**
   * Constructs a new AddToCartForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_order\Resolver\OrderTypeResolverInterface $order_type_resolver
   *   The order type resolver.
   * @param \Drupal\commerce_store\StoreContextInterface $store_context
   *   The store context.
   * @param \Drupal\commerce_price\Resolver\ChainPriceResolverInterface $chain_price_resolver
   *   The chain base price resolver.
   */
  public function __construct(EntityManagerInterface $entity_manager, CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, OrderTypeResolverInterface $order_type_resolver, StoreContextInterface $store_context, ChainPriceResolverInterface $chain_price_resolver) {
    parent::__construct($entity_manager);

    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->orderTypeResolver = $order_type_resolver;
    $this->storeContext = $store_context;
    $this->chainPriceResolver = $chain_price_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_order.chain_order_type_resolver'),
      $container->get('commerce_store.store_context'),
      $container->get('commerce_price.chain_price_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return $this->entity->getEntityTypeId() . '_' . $this->operation . '_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $product_id = $this->entity->getPurchasedEntity()->getProductId();
    $form_id = $this->entity->getEntityTypeId();
    if ($this->entity->getEntityType()->hasKey('bundle')) {
      $form_id .= '_' . $this->entity->bundle();
    }
    if ($this->operation != 'default') {
      $form_id = $form_id . '_' . $this->operation;
    }
    $form_id .= '_' . $product_id;

    return $form_id . '_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // The widgets are allowed to signal that the form should be hidden
    // (because there's no purchasable entity to select, for example).
    if ($form_state->get('hide_form')) {
      $form['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add to cart'),
      '#submit' => ['::submitForm'],
    ];

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->entity;
    /** @var \Drupal\commerce\PurchasableEntityInterface $purchased_entity */
    $purchased_entity = $order_item->getPurchasedEntity();

    $order_type = $this->orderTypeResolver->resolve($order_item);

    $store = $this->selectStore($purchased_entity);
    $cart = $this->cartProvider->getCart($order_type, $store);
    if (!$cart) {
      $cart = $this->cartProvider->createCart($order_type, $store);
    }
    $this->cartManager->addOrderItem($cart, $order_item, $form_state->get(['settings', 'combine']));

    drupal_set_message($this->t('@entity added to @cart-link.', [
      '@entity' => $purchased_entity->label(),
      '@cart-link' => Link::createFromRoute($this->t('your cart', [], ['context' => 'cart link']), 'commerce_cart.page')->toString(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $entity */
    $entity = parent::buildEntity($form, $form_state);
    // Now that the purchased entity is set, populate the title and price.
    $entity->setTitle($entity->getPurchasedEntity()->getOrderItemTitle());
    $entity->setUnitPrice($this->chainPriceResolver->resolve($entity->getPurchasedEntity()));

    return $entity;
  }

  /**
   * Selects the store for the given purchasable entity.
   *
   * If the entity is sold from one store, then that store is selected.
   * If the entity is sold from multiple stores, and the current store is
   * one of them, then that store is selected.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The entity being added to cart.
   *
   * @throws \Exception
   *   When the entity can't be purchased from the current store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface
   *   The selected store.
   */
  protected function selectStore(PurchasableEntityInterface $entity) {
    $stores = $entity->getStores();
    if (count($stores) === 1) {
      $store = reset($stores);
    }
    else {
      $store = $this->storeContext->getStore();
      if (!in_array($store, $stores)) {
        // Indicates that the site listings are not filtered properly.
        throw new \Exception("The given entity can't be purchased from the current store.");
      }
    }

    return $store;
  }

}
