<?php
/**
 * @file
 * Contains \Drupal\commerce_payment\PaymentInfoListBuilder.
 */

namespace Drupal\commerce_payment;

use Drupal\commerce_payment\Entity\PaymentInfoType;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for commerce_payment_info entity.
 */
class PaymentInfoListBuilder extends EntityListBuilder {

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new PaymentInfoListBuilder object.
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
      'information_id' => array(
        'data' => $this->t('Information ID'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'payment_method' => array(
        'data' => $this->t('Payment method'),
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
      'changed' => array(
        'data' => $this->t('Changed'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
    );
    return $header + parent::buildHeader();
  }
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_payment\Entity\PaymentInfo */
    $paymentInfoType = PaymentInfoType::load($entity->bundle());

    $row = array(
      'information_id' => $entity->id(),
      'payment_method' => $paymentInfoType->label(),
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
