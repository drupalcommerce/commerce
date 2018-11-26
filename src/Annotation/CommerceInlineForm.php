<?php

namespace Drupal\commerce\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the inline form plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\InlineForm.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class CommerceInlineForm extends Plugin {

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

}
