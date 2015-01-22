<?php
/**
 * @file
 * Contains \Drupal\commerce_line_item\CommerceLineItemListBuilder.
 */

namespace Drupal\commerce_line_item;

use Drupal\commerce_line_item\Entity\CommerceLineItemType;
use Drupal\Component\Utility\String;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for commerce_line_item entity.
 */
class CommerceLineItemListBuilder extends EntityListBuilder {

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new CommerceLineItemListBuilder object.
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
    $header = array(
      'line_item_id' => array(
        'data' => $this->t('Line Item ID'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'type' => array(
        'data' => $this->t('Line item type'),
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
      'owner' => array(
        'data' => $this->t('Owner'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'status' => $this->t('Status'),
      'created' => array(
        'data' => $this->t('Created'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'updated' => array(
        'data' => $this->t('Updated'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
    );
    return $header + parent::buildHeader();
  }
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_line_item\Entity\CommerceLineItem */
    $commerceLineItemType = CommerceLineItemType::load($entity->bundle());

    if (!empty($commerceLineItemType)) {
      $type = String::checkPlain($commerceLineItemType->label());
    }
    else {
      $type = String::checkPlain($entity->bundle());
    }

    $row = array(
      'line_item_id' => $entity->id(),
      'type' => $type,
      'owner' => array(
        'data' => array(
          '#theme' => 'username',
          '#account' => $entity->getOwner(),
        ),
      ),
      'status' => $entity->getStatus(),
      'created' => $this->dateFormatter->format($entity->getCreatedTime(), 'short'),
      'changed' => $this->dateFormatter->format($entity->getChangedTime(), 'short'),
    );
    return $row + parent::buildRow($entity);
  }

}
