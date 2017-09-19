<?php

namespace Drupal\commerce_log\EventSubscriber;

use Drupal\commerce_log\Event\LogEvents;
use Drupal\commerce_log\Event\ProductVariationChangedFieldsFilterEvent;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\commerce_product\Event\ProductVariationEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductVariationEventSubscriber implements EventSubscriberInterface {

  /**
   * The log storage.
   *
   * @var \Drupal\commerce_log\LogStorageInterface
   */
  protected $logStorage;

  /**
   * The dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new ProductVariationEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EventDispatcherInterface $eventDispatcher) {
    $this->logStorage = $entityTypeManager->getStorage('commerce_log');
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      ProductEvents::PRODUCT_VARIATION_PRESAVE => ['onVariationPresave', -100],
      ProductEvents::PRODUCT_VARIATION_PREDELETE => ['onVariationPredelete', -100],
    ];
    return $events;
  }

  /**
   * Creates a log before saving a product variation.
   *
   * @param \Drupal\commerce_product\Event\ProductVariationEvent $event
   *   The variation event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onVariationPresave(ProductVariationEvent $event) {
    if ($product = $event->getProductVariation()->getProduct()) {
      $variation = $event->getProductVariation();
      $original = $variation->original;
      // If there isn't a related product yet, then it is a new variation.
      if (!$original->getProduct()) {
        $this->logStorage->generate($product, 'variation_added', [
          'id' => $event->getProductVariation()->id(),
          'sku' => $event->getProductVariation()->getSku(),
          'label' => $event->getProductVariation()->label(),
        ])->save();
      }
      elseif ($changedValues = $this->getChangedFields($original, $variation)) {
        $this->logStorage->generate($product, 'variation_field_changed', [
          'id' => $event->getProductVariation()->id(),
          'sku' => $event->getProductVariation()->getSku(),
          'changed_fields' => json_encode($changedValues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
        ])->save();
      }
    }
  }

  /**
   * Creates a log when a product variation is deleted.
   *
   * @param \Drupal\commerce_product\Event\ProductVariationEvent $event
   *   The variation event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onVariationPredelete(ProductVariationEvent $event) {
    $this->logStorage->generate($event->getProductVariation()->getProduct(), 'variation_deleted', [
      'id' => $event->getProductVariation()->id(),
      'sku' => $event->getProductVariation()->getSku(),
    ])->save();
  }

  /**
   * Determine the changed fields and their values.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $original
   *   The original product variation.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $new
   *   The new product variation.
   *
   * @return array
   *   A list of changed values
   */
  protected function getChangedFields(ProductVariationInterface $original, ProductVariationInterface $new) {
    $changedFields = new ProductVariationChangedFieldsFilterEvent(['price', 'sku', 'status', 'title']);
    $this->eventDispatcher->dispatch(LogEvents::PRODUCT_VARIATION_CHANGED_FIELDS_FILTER, $changedFields);
    $changedValues = [];
    foreach ($changedFields->getFields() as $field) {
      $newValue = $new->{$field}->getValue();
      $originalValue = $original->{$field}->getValue();
      if ($newValue != $originalValue) {
        $changedValues[$field] = [
          'Original value' => $originalValue,
          'New value' => $newValue,
        ];
      }
    }
    return $changedValues;
  }

}
