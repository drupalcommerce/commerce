<?php

namespace Drupal\commerce_order\Plugin\Validation\Constraint;

use Drupal\commerce\AvailabilityManagerInterface;
use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_store\SelectStoreTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Constraint validator for validating purchased entity availability.
 */
class PurchasedEntityAvailableConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use SelectStoreTrait;

  /**
   * The availability manager.
   *
   * @var \Drupal\commerce\AvailabilityManagerInterface
   */
  protected $availabilityManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new PurchasedEntityAvailableConstraintValidator object.
   *
   * @param \Drupal\commerce\AvailabilityManagerInterface $availability_manager
   *   The availability manager.
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AvailabilityManagerInterface $availability_manager, CurrentStoreInterface $current_store, AccountInterface $current_user) {
    $this->availabilityManager = $availability_manager;
    $this->currentStore = $current_store;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce.availability_manager'),
      $container->get('commerce_store.current_store'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    assert($value instanceof EntityReferenceFieldItemListInterface);
    if ($value->isEmpty()) {
      return;
    }

    $order_item = $value->getEntity();
    assert($order_item instanceof OrderItemInterface);
    $purchased_entity = $order_item->getPurchasedEntity();
    if (!$purchased_entity instanceof PurchasableEntityInterface) {
      // An invalid reference will be handled by the ValidReference constraint.
      return;
    }

    $order = $order_item->getOrder();
    if (!$order instanceof OrderInterface) {
      // This may be a new order item that hasn't been assigned to an order yet,
      // so provide a default customer and store for the context.
      $customer = $this->currentUser;
      try {
        $store = $this->selectStore($purchased_entity);
      }
      catch (\Exception $e) {
        $this->context->addViolation($e->getMessage());
        return;
      }
    }
    elseif ($order->getState()->getId() === 'draft') {
      $customer = $order->getCustomer();
      $store = $order->getStore();
    }
    else {
      // Do not process non-draft orders.
      return;
    }

    $quantity = $order_item->getQuantity();
    $context = new Context($customer, $store);
    $availability = $this->availabilityManager->check($purchased_entity, $quantity, $context);
    if (!$availability) {
      $this->context->buildViolation($constraint->message, [
        '%label' => $purchased_entity->label(),
        '%quantity' => $quantity,
      ])->addViolation();
    }
  }

}
