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
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   The date service.
   */
  public function __construct(EntityTypeInterface $entityType, EntityTypeManagerInterface $entityTypeManager, DateFormatter $dateFormatter) {
    parent::__construct($entityType, $entityTypeManager->getStorage($entityType->id()));

    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entityType) {
    return new static(
      $entityType,
      $container->get('entity_type.manager'),
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
        'data' => $this->t('Type'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'customer' => [
        'data' => $this->t('Customer'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'state' => [
        'data' => $this->t('State'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'created' => [
        'data' => $this->t('Created'),
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
      'customer' => [
        'data' => [
          '#theme' => 'username',
          '#account' => $entity->getOwner(),
        ],
      ],
      'state' => $entity->getState()->getLabel(),
      'created' => $this->dateFormatter->format($entity->getCreatedTime(), 'short'),
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity->access('update') && $entity->hasLinkTemplate('reassign-form')) {
      $operations['reassign'] = array(
        'title' => $this->t('Reassign'),
        'weight' => 20,
        'url' => $entity->toUrl('reassign-form'),
      );
    }

    return $operations;
  }

}
