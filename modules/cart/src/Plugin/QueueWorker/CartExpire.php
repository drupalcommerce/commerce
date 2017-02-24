<?php

namespace Drupal\commerce_cart\Plugin\QueueWorker;

use Drupal\commerce\TimeInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Removes an expired Cart.
 *
 * @QueueWorker(
 *  id = "cart_expirations",
 *  title = @Translation("Commerce Cart expiration"),
 *  cron = {}
 * )
 */
class CartExpire extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The commerce time service.
   *
   * @var \Drupal\commerce\Time
   */
  protected $time;

  /**
   * Constructs a new CartBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce\TimeInterface $time
   *   The time service.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('commerce.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $orders = $this->entityTypeManager->getStorage('commerce_order')
      ->loadMultiple($data);
    foreach ($orders as $order) {
      // Ensure that orders slated for clearing have not been completed since
      // they were last queued.
      if ($order instanceof OrderInterface && $order->cart->value == 1) {
        $order_type_storage = $this->entityTypeManager
          ->getStorage('commerce_order_type');
        $order_type = $order_type_storage->load($order->bundle());
        $elapsed = $this->time->getCurrentTime() - $order->getCreatedTime();
        $expiry = $order_type->getThirdPartySetting('commerce_cart', 'cart_expiration') * 3600 * 24;
        if ($elapsed >= $expiry) {
          $order->delete();
        }
      }
    }
  }

}
