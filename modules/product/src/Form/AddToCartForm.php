<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Form\AddToCartForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_store\StoreContextInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the add to cart form for product variations.
 */
class AddToCartForm extends FormBase {

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
   * The variation storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $variationStorage;

  /**
   * The store context.
   *
   * @var \Drupal\commerce_store\StoreContextInterface
   */
  protected $storeContext;

  /**
   * Constructs a new AddToCartForm object.
   *
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
  49   The entity type manager.
   * @param \Drupal\commerce_store\StoreContextInterface $store_context
   *   The store context.
   */
  public function __construct(CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, EntityTypeManagerInterface $entity_type_manager, StoreContextInterface $store_context) {
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->variationStorage = $entity_type_manager->getStorage('commerce_product_variation');
    $this->storeContext = $store_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('entity_type.manager'),
      $container->get('commerce_store.store_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_product_add_to_cart_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $product = NULL, array $settings = NULL) {
    $variations = [];
    foreach ($product->variations->referencedEntities() as $variation) {
      $variations[$variation->id()] = $variation->label();
    }

    $form['#settings'] = $settings;
    $form['variation'] = [
      '#type' => 'select',
      '#title' => $this->t('Select variation:'),
      '#options' => $variations,
    ];
    if (!empty($settings['show_quantity'])) {
      $form['quantity'] = [
        '#type' => 'number',
        '#title' => $this->t('Quantity'),
        '#min' => 1,
        '#max' => 9999,
        '#step' => 1,
      ];
    }
    else {
      $form['quantity'] = [
        '#type' => 'value',
        '#value' => $settings['default_quantity'],
      ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add to cart'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, FormStateInterface $form_state) {
    $variation = $this->variationStorage->load($form_state->getValue('variation'));
    $available_stores = $variation->getProduct()->getStores();
    if (count($available_stores) === 1) {
      $store = reset($available_stores);
    }
    else {
      $store = $this->storeContext->getStore();
      if (!in_array($store, $available_stores)) {
        // Throw an exception.
      }
    }
    // @todo The order type should not be hardcoded.
    $cart = $this->cartProvider->getCart('default', $store);
    if (!$cart) {
      $cart = $this->cartProvider->createCart('default', $store);
    }
    $quantity = $form_state->getValue('quantity');
    $combine = $form['#settings']['combine'];
    $this->cartManager->addEntity($cart, $variation, $quantity, $combine);
    drupal_set_message(t('@variation added to @cart-link.', [
      '@variation' => $variation->label(),
      '@cart-link' => Link::createFromRoute('your cart', 'commerce_cart.page')->toString(),
    ]));
  }

}
