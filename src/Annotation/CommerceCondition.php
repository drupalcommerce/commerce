<?php

namespace Drupal\commerce\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the condition plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\Condition.
 *
 * @Annotation
 */
class CommerceCondition extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The condition label.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The condition display label.
   *
   * Shown in the condition UI when enabling/disabling a condition.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $display_label;

  /**
   * The condition category.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $category;

  /**
   * The condition entity type ID.
   *
   * This is the entity type ID of the entity passed to the plugin during
   * evaluation. For example: 'commerce_order'.
   *
   * @var string
   */
  public $entity_type;

  /**
   * Constructs a new CommerceCondition object.
   *
   * @param array $values
   *   The annotation values.
   */
  public function __construct(array $values) {
    if (empty($values['category'])) {
      $values['category'] = t('Other');
    }
    parent::__construct($values);
  }

}
