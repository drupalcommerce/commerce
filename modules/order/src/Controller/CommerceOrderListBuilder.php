<?php
/**
 * @file
 * Contains \Drupal\commerce_order\Controller\CommerceOrderListBuilder.
 */
namespace Drupal\commerce_order\Controller;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
/**
 * Provides a list controller for commerce_order entity.
 */
class CommerceOrderListBuilder extends EntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['order_id'] = t('Order id');
    $header['created'] = t('Created');
    return $header + parent::buildHeader();
  }
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['order_id'] = $entity->id();
    $row['created'] = $entity->getCreated();
    return $row + parent::buildRow($entity);
  }
}
