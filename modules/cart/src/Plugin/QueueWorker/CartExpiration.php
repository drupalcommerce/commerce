<?php

namespace Drupal\commerce_cart\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\commerce\Interval;

/**
 * Deletes expired carts.
 *
 * @QueueWorker(
 *  id = "commerce_cart_expiration",
 *  title = @Translation("Cart expiration"),
 *  cron = {"time" = 30}
 * )
 */
class CartExpiration extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The order storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderStorage;

  /**
   * The order type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderTypeStorage;

  /**
   * Constructs a new CartExpiration object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
    $this->orderTypeStorage = $entity_type_manager->getStorage('commerce_order_type');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $orders = [];
    foreach ($data as $order_id) {
      // Skip the OrderRefresh process to keep the changed timestamp intact.
      /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
      $order = $this->orderStorage->loadUnchanged($order_id);
      if (!$order) {
        continue;
      }
      /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
      $order_type = $this->orderTypeStorage->load($order->bundle());
      $cart_expiration = $order_type->getThirdPartySetting('commerce_cart', 'cart_expiration');
      // Confirm that cart expiration has not been disabled after queueing.
      if (empty($cart_expiration)) {
        continue;
      }

      $current_date = new DrupalDateTime('now');
      $interval = new Interval($cart_expiration['number'], $cart_expiration['unit']);
      $expiration_date = $interval->subtract($current_date);
      $expiration_timestamp = $expiration_date->getTimestamp();
      // Make sure that the cart order still qualifies for expiration.
      if ($order->get('cart')->value && $order->getChangedTime() <= $expiration_timestamp) {
        $orders[] = $order;
      }
    }

    $this->orderStorage->delete($orders);
  }

}
