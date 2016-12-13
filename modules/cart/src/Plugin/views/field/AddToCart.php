<?php

namespace Drupal\commerce_cart\Plugin\views\field;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_store\StoreContextInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\UncacheableFieldHandlerTrait;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form element for adding a product variation to a cart.
 *
 * @ViewsField("commerce_cart_add_to_cart")
 */
class AddToCart extends FieldPluginBase {

  use UncacheableFieldHandlerTrait;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * @var \Drupal\commerce_store\StoreContextInterface
   */
  protected $storeContext;
  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * Constructs a new EditQuantity object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   * @param \Drupal\commerce_store\StoreContextInterface $store_context
   * @param \Drupal\Core\Session\AccountInterface $current_user
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, StoreContextInterface $store_context, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->storeContext = $store_context;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_store.store_context'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $row, $field = NULL) {
    // Make sure the current user is allowed to use the cart.
    if (!$this->currentUser->hasPermission('access cart')) {
      return;
    }
    return '<!--form-item-' . $this->options['id'] . '--' . $row->index . '-->';
  }

  /**
   * Form constructor for the views form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    // Hijack the original submit button and mold it to our use case.
    $add_to_cart_button = $form['actions']['submit'];
    $add_to_cart_button['#value'] = $this->t('Add to cart');
    unset($form['actions']['submit']);

    // Make sure the current user is allowed to use the cart.
    if (!$this->currentUser->hasPermission('access cart')) {
      return;
    }
    // Make sure we do not accidentally cache this form.
    $form['#cache']['max-age'] = 0;
    // The view is empty, abort.
    if (empty($this->view->result)) {
      unset($form['actions']);
      return;
    }

    $form[$this->options['id']]['#tree'] = TRUE;
    foreach ($this->view->result as $row_index => $row) {
      $form[$this->options['id']][$row_index][] = [
        '#type' => 'number',
        '#title' => $this->t('Quantity'),
        '#title_display' => 'invisible',
        '#default_value' => round(0),
        '#size' => 4,
        '#min' => 0,
        '#max' => 9999,
        '#step' => 1,
      ];
      $form[$this->options['id']][$row_index][] = $add_to_cart_button;
    }
  }

  /**
   * Submit handler for the views form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsFormSubmit(&$form, FormStateInterface $form_state) {
    $quantities = $form_state->getValue($this->options['id']);
    foreach ($quantities as $row_index => $quantity) {
      if (empty($quantity[0])) {
        continue;
      }
      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation */
      $product_variation = $this->getEntity($this->view->result[$row_index]);

      $order_item = $this->cartManager->createOrderItem($product_variation, $quantity[0]);

      $cart = $this->cartProvider->getCart('default', $this->storeContext->getStore());
      if (!$cart) {
        $cart = $this->cartProvider->createCart('default', $this->storeContext->getStore());
      }

      $this->cartManager->addOrderItem($cart, $order_item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    return FALSE;
  }

}
