<?php

namespace Drupal\commerce\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the entity trait plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\EntityTrait.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class CommerceEntityTrait extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin label.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The entity types that can have this trait.
   *
   * When empty, defaults to all entity types.
   *
   * @var array
   */
  public $entity_types;

}
