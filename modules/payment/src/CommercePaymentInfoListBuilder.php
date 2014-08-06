<?php
/**
 * @file
 * Contains \Drupal\commerce_payment\CommercePaymentInfoListBuilder.
 */

namespace Drupal\commerce_payment;

use Drupal\commerce_payment\Entity\CommercePaymentInfoType;
use Drupal\Component\Utility\String;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for commerce_payment_info entity.
 */
class CommercePaymentInfoListBuilder extends EntityListBuilder {

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $date_formatter;

  /**
   * Constructs a new CommercePaymentInfoListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatter $date_formatter) {
    parent::__construct($entity_type, $storage);

    $this->date_formatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
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
    /* @var $entity \Drupal\commerce_payment\Entity\CommercePaymentInfo */
    $payment_info_type = CommercePaymentInfoType::load($entity->bundle());

    if (!empty($payment_info_type)) {
      $type = String::checkPlain($payment_info_type->label());
    }
    else {
      $type = String::checkPlain($entity->bundle());
    }

    $row = array(
      'information_id' => $entity->id(),
      'payment_method' => $type,
      'owner' => array(
        'data' => array(
          '#theme' => 'username',
          '#account' => $entity->getOwner(),
        ),
      ),
      'status' => $entity->getStatus(),
      'created' => $this->date_formatter->format($entity->getCreatedTime(), 'short'),
      'changed' => $this->date_formatter->format($entity->getChangedTime(), 'short'),
    );
    return $row + parent::buildRow($entity);
  }

}
