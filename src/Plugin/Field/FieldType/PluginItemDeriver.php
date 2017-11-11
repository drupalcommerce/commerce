<?php

namespace Drupal\commerce\Plugin\Field\FieldType;

use Drupal\commerce\Event\CommerceEvents;
use Drupal\commerce\Event\ReferenceablePluginTypesEvent;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Deriver for the commerce_plugin_item field type.
 */
class PluginItemDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new PluginItemDeriver object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct($base_plugin_id, EventDispatcherInterface $event_dispatcher) {
    $this->basePluginId = $base_plugin_id;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $plugin_types = [
      'commerce_condition' => $this->t('Condition'),
      'commerce_promotion_offer' => $this->t('Promotion offer'),
    ];
    // Core has no way to list plugin types, so each referenceable plugin
    // type needs to register itself via the event.
    $event = new ReferenceablePluginTypesEvent($plugin_types);
    $this->eventDispatcher->dispatch(CommerceEvents::REFERENCEABLE_PLUGIN_TYPES, $event);
    $plugin_types = $event->getPluginTypes();

    foreach ($plugin_types as $plugin_type => $label) {
      $this->derivatives[$plugin_type] = [
        'plugin_type' => $plugin_type,
        'label' => $label,
        'category' => $this->t('Plugin'),
      ] + $base_plugin_definition;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
