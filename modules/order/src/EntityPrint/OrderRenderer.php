<?php

namespace Drupal\commerce_order\EntityPrint;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderTotalSummaryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface as CoreRendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_print\Asset\AssetRendererInterface;
use Drupal\entity_print\FilenameGeneratorInterface;
use Drupal\entity_print\Renderer\ContentEntityRenderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a custom entity_print renderer for orders.
 *
 * Uses the commerce_order_receipt template for the document contents.
 */
class OrderRenderer extends ContentEntityRenderer {

  use StringTranslationTrait;

  /**
   * The order total summary.
   *
   * @var \Drupal\commerce_order\OrderTotalSummaryInterface
   */
  protected $orderTotalSummary;

  /**
   * Constructs a new OrderEntityRenderer object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The core render service.
   * @param \Drupal\entity_print\Asset\AssetRendererInterface $asset_renderer
   *   The entity print asset renderer.
   * @param \Drupal\entity_print\FilenameGeneratorInterface $filename_generator
   *   A filename generator.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_order\OrderTotalSummaryInterface $order_total_summary
   *   The order total summary service.
   */
  public function __construct(CoreRendererInterface $renderer, AssetRendererInterface $asset_renderer, FilenameGeneratorInterface $filename_generator, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, OrderTotalSummaryInterface $order_total_summary) {
    parent::__construct($renderer, $asset_renderer, $filename_generator, $event_dispatcher, $entity_type_manager);

    $this->orderTotalSummary = $order_total_summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('renderer'),
      $container->get('entity_print.asset_renderer'),
      $container->get('entity_print.filename_generator'),
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('commerce_order.order_total_summary')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $orders) {
    $profile_view_builder = $this->entityTypeManager->getViewBuilder('profile');
    $build = [];
    foreach ($orders as $order) {
      assert($order instanceof OrderInterface);
      $order_build = [
        '#theme' => 'commerce_order_receipt__entity_print',
        '#order_entity' => $order,
        '#totals' => $this->orderTotalSummary->buildTotals($order),
      ];
      if ($billing_profile = $order->getBillingProfile()) {
        $order_build['#billing_information'] = $profile_view_builder->view($billing_profile);
      }
      $build[] = $order_build;
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilename(array $entities) {
    $entities_label = $this->filenameGenerator->generateFilename($entities, static function (OrderInterface $order) {
      return $order->id();
    });
    return $this->t('Order @id @receipt', [
      '@id' => $entities_label,
      '@receipt' => $this->formatPlural(count($entities), 'receipt', 'receipts'),
    ]);
  }

}
