<?php

namespace Drupal\commerce_order\Plugin\Commerce\Condition;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PurchasedEntityConditionDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PurchasedEntityConditionDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $purchasable_entity_types = array_filter($this->entityTypeManager->getDefinitions(), static function (EntityTypeInterface $entity_type) {
      return $entity_type->entityClassImplements(PurchasableEntityInterface::class);
    });

    foreach ($purchasable_entity_types as $purchasable_entity_type_id => $purchasable_entity_type) {
      if ($base_plugin_definition['entity_type'] === 'commerce_order') {
        $display_label = new TranslatableMarkup('Order contains specific :item', [':item' => $purchasable_entity_type->getPluralLabel()]);
      }
      else {
        $display_label = new TranslatableMarkup('Specific :item', [':item' => $purchasable_entity_type->getSingularLabel()]);
      }

      $this->derivatives[$purchasable_entity_type_id] = [
        'label' => $purchasable_entity_type->getLabel(),
        'display_label' => $display_label,
        'purchasable_entity_type' => $purchasable_entity_type_id,
      ] + $base_plugin_definition;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
