<?php

namespace Drupal\commerce_checkout\Plugin\Block;

use Drupal\commerce_checkout\CheckoutOrderManagerInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a checkout progress block.
 *
 * @Block(
 *   id = "commerce_checkout_progress",
 *   admin_label = @Translation("Checkout progress"),
 *   category = @Translation("Commerce")
 * )
 */
class CheckoutProgressBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The checkout order manager.
   *
   * @var \Drupal\commerce_checkout\CheckoutOrderManagerInterface
   */
  protected $checkoutOrderManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new CheckoutProgressBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\CheckoutOrderManagerInterface $checkout_order_manager
   *   The checkout order manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutOrderManagerInterface $checkout_order_manager, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->checkoutOrderManager = $checkout_order_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_checkout.checkout_order_manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * Builds the checkout progress block.
   *
   * @return array
   *   A render array.
   */
  public function build() {
    $order = $this->routeMatch->getParameter('commerce_order');
    if (!$order) {
      // The block is being rendered outside of the checkout page.
      return [];
    }
    $checkout_flow = $this->checkoutOrderManager->getCheckoutFlow($order);
    $checkout_flow_plugin = $checkout_flow->getPlugin();
    $configuration = $checkout_flow_plugin->getConfiguration();
    if (empty($configuration['display_checkout_progress'])) {
      return [];
    }

    // Prepare the steps as expected by the template.
    $steps = [];
    $visible_steps = $checkout_flow_plugin->getVisibleSteps();
    $requested_step_id = $this->routeMatch->getParameter('step');
    $current_step_id = $this->checkoutOrderManager->getCheckoutStepId($order, $requested_step_id);
    $current_step_index = array_search($current_step_id, array_keys($visible_steps));
    $index = 0;
    foreach ($visible_steps as $step_id => $step_definition) {
      if ($index < $current_step_index) {
        $position = 'previous';
      }
      elseif ($index == $current_step_index) {
        $position = 'current';
      }
      else {
        $position = 'next';
      }
      $index++;
      // Hide hidden steps until they are reached.
      if (!empty($step_definition['hidden']) && $position != 'current') {
        continue;
      }

      // Create breadcrumb style links for active checkout steps.
      if (
        $current_step_id !== 'complete' &&
        $configuration['display_checkout_progress_breadcrumb_links'] &&
        $index <= $current_step_index
      ) {
        $label = Link::createFromRoute($step_definition['label'], 'commerce_checkout.form', [
          'commerce_order' => $order->id(),
          'step' => $step_id,
        ])->toString();
      }
      else {
        $label = $step_definition['label'];
      }

      $steps[] = [
        'id' => $step_id,
        'label' => $label,
        'position' => $position,
      ];
    }

    return [
      '#attached' => [
        'library' => ['commerce_checkout/checkout_progress'],
      ],
      '#theme' => 'commerce_checkout_progress',
      '#steps' => $steps,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
