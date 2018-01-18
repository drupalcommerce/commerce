<?php

namespace Drupal\commerce_order;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Resolver\OrderTypeResolverInterface;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PriceCalculator implements PriceCalculatorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The chain base price resolver.
   *
   * @var \Drupal\commerce_price\Resolver\ChainPriceResolverInterface
   */
  protected $chainPriceResolver;

  /**
   * The order type resolver.
   *
   * @var \Drupal\commerce_order\Resolver\OrderTypeResolverInterface
   */
  protected $orderTypeResolver;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The order processors.
   *
   * @var \Drupal\commerce_order\OrderProcessorInterface[]
   */
  protected $processors = [];

  /**
   * The unsaved orders used for calculations.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface[]
   */
  protected $orders = [];

  /**
   * Constructs a new PriceCalculator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_price\Resolver\ChainPriceResolverInterface $chain_price_resolver
   *   The chain price resolver.
   * @param \Drupal\commerce_order\Resolver\OrderTypeResolverInterface $order_type_resolver
   *   The order type resolver.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ChainPriceResolverInterface $chain_price_resolver, OrderTypeResolverInterface $order_type_resolver, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->chainPriceResolver = $chain_price_resolver;
    $this->orderTypeResolver = $order_type_resolver;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function addProcessor(OrderProcessorInterface $processor, $adjustment_type) {
    $this->processors[$adjustment_type][] = $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessors($adjustment_type) {
    if (!isset($this->processors[$adjustment_type])) {
      return [];
    }
    return $this->processors[$adjustment_type];
  }

  /**
   * {@inheritdoc}
   */
  public function calculate(PurchasableEntityInterface $purchasable_entity, $quantity, Context $context, array $adjustment_types = []) {
    $resolved_price = $this->chainPriceResolver->resolve($purchasable_entity, $quantity, $context);
    $processors = [];
    foreach ($adjustment_types as $adjustment_type) {
      $processors = array_merge($processors, $this->getProcessors($adjustment_type));
    }
    if (empty($adjustment_types) || empty($processors)) {
      return [
        'calculated_price' => $resolved_price,
        'base_price' => $resolved_price,
        'adjustments' => [],
      ];
    }

    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->entityTypeManager->getStorage('commerce_order_item');
    $order_item = $order_item_storage->createFromPurchasableEntity($purchasable_entity);
    $order_item->setUnitPrice($resolved_price);
    $order_item->setQuantity($quantity);
    $order_type_id = $this->orderTypeResolver->resolve($order_item);

    $order = $this->prepareOrder($order_type_id, $context);
    $order_item->order_id = $order;
    $order->setItems([$order_item]);
    // Allow each selected processor to add its adjustments.
    foreach ($processors as $processor) {
      $processor->process($order);
    }

    return [
      'calculated_price' => $order_item->getAdjustedUnitPrice(),
      'base_price' => $resolved_price,
      'adjustments' => $order_item->getAdjustments(),
    ];
  }

  /**
   * Prepares an unsaved order for the given type/context.
   *
   * @param string $order_type_id
   *   The order type ID.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  protected function prepareOrder($order_type_id, Context $context) {
    if (!isset($this->orders[$order_type_id])) {
      $order_storage = $this->entityTypeManager->getStorage('commerce_order');
      $this->orders[$order_type_id] = $order_storage->create([
        'type' => $order_type_id,
        'ip_address' => $this->requestStack->getCurrentRequest()->getClientIp(),
        // Provide a flag that can be used in the order create hook/event
        // to identify orders used for price calculation purposes.
        'data' => ['provider' => 'order_price_calculator'],
      ]);
    }

    $order = $this->orders[$order_type_id];
    // Make sure that the order data matches the data passed in the context.
    $order->setStoreId($context->getStore()->id());
    $order->setCustomerId($context->getCustomer()->id());
    $order->setEmail($context->getCustomer()->getEmail());
    // Start from a clear set of adjustments each time.
    $order->clearAdjustments();

    return $order;
  }

}
