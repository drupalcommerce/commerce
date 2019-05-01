<?php

namespace Drupal\commerce_checkout\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the checkout pane plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\CheckoutPane.
 *
 * @Annotation
 */
class CommerceCheckoutPane extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The checkout pane label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The checkout pane display label.
   *
   * Defaults to the main label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $display_label;

  /**
   * The ID of the default step for this pane.
   *
   * Optional. If missing, the pane will be disabled by default.
   *
   * @var string
   */
  public $default_step;

  /**
   * The wrapper element to use when rendering the pane's form.
   *
   * E.g: 'container', 'fieldset'. Defaults to 'container'.
   *
   * @var string
   */
  public $wrapper_element;

  /**
   * Constructs a new CommerceCheckoutPane object.
   *
   * @param array $values
   *   The annotation values.
   */
  public function __construct(array $values) {
    if (empty($values['display_label'])) {
      $values['display_label'] = $values['label'];
    }
    parent::__construct($values);
  }

}
