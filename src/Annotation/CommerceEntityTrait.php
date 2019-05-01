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
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The content entity types that can have this trait.
   *
   * The bundle entities of the content entity type will reference the trait,
   * and receive any fields it defines.
   *
   * When empty, defaults to all content entity types.
   *
   * @var array
   */
  public $entity_types;

}
