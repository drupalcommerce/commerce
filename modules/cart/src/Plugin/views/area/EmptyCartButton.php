<?php

namespace Drupal\commerce_cart\Plugin\views\area;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an area handler for the "Empty cart" button.
 *
 * @ViewsArea("commerce_order_empty_cart_button")
 */
class EmptyCartButton extends AreaPluginBase {

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The order storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderStorage;

  /**
   * Constructs a new EmptyCartButton object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CartManagerInterface $cart_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->cartManager = $cart_manager;
    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    return [];
  }

  /**
   * Gets whether the views form should be shown when the view has no results.
   *
   * @param bool $empty
   *   Whether the view has results.
   *
   * @return bool
   *   True if the views form should be shown, FALSE otherwise.
   */
  public function viewsFormEmpty($empty) {
    return $empty;
  }

  /**
   * Builds the views form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    // Make sure we do not accidentally cache this form.
    $form['#cache']['max-age'] = 0;

    $form[$this->options['id']]['#tree'] = TRUE;
    $form['actions']['empty_cart'] = [
      '#type' => 'submit',
      '#value' => t('Empty cart'),
      '#empty_cart_button' => TRUE,
      '#attributes' => [
        'class' => ['empty-cart-button'],
      ],
    ];
  }

  /**
   * Submits the views form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element['#empty_cart_button'])) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
      $cart = $this->orderStorage->load($this->view->argument['order_id']->getValue());
      $this->cartManager->emptyCart($cart);

      drupal_set_message($this->t('Your shopping cart has been emptied.'));
    }
  }

}
