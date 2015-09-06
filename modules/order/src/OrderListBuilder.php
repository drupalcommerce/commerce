<?php
/**
 * @file
 * Contains \Drupal\commerce_order\OrderListBuilder.
 */

namespace Drupal\commerce_order;

use Drupal\commerce_order\Entity\OrderType;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder for orders.
 */
class OrderListBuilder extends EntityListBuilder {

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new OrderListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   The date service.
   */
  public function __construct(EntityTypeInterface $entityType, EntityStorageInterface $storage, DateFormatter $dateFormatter) {
    parent::__construct($entityType, $storage);

    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entityType) {
    return new static(
      $entityType,
      $container->get('entity.manager')->getStorage($entityType->id()),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'order_id' => [
        'data' => $this->t('Order ID'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'type' => [
        'data' => $this->t('Order type'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'owner' => [
        'data' => $this->t('Owner'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'status' => $this->t('Status'),
      'created' => [
        'data' => $this->t('Created'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'updated' => [
        'data' => $this->t('Updated'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_order\Entity\Order */
    $orderType = OrderType::load($entity->bundle());
    $row = [
      'order_id' => $entity->id(),
      'type' => $orderType->label(),
      'owner' => [
        'data' => [
          '#theme' => 'username',
          '#account' => $entity->getOwner(),
        ],
      ],
      'status' => $entity->getStatus(),
      'created' => $this->dateFormatter->format($entity->getCreatedTime(), 'short'),
      'changed' => $this->dateFormatter->format($entity->getChangedTime(), 'short'),
    ];

    return $row + parent::buildRow($entity);
  }

}
