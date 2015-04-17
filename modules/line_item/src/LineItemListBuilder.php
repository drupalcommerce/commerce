<?php
/**
 * @file
 * Contains \Drupal\commerce_line_item\LineItemListBuilder.
 */

namespace Drupal\commerce_line_item;

use Drupal\commerce_line_item\Entity\LineItemType;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for commerce_line_item entity.
 */
class LineItemListBuilder extends EntityListBuilder {

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new LineItemListBuilder object.
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
      'line_item_id' => [
        'data' => $this->t('Line Item ID'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'type' => [
        'data' => $this->t('Line item type'),
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
    /* @var $entity \Drupal\commerce_line_item\Entity\LineItem */
    $lineItemType = LineItemType::load($entity->bundle());
    $row = [
      'line_item_id' => $entity->id(),
      'type' => SafeMarkup::checkPlain($lineItemType->label()),
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
