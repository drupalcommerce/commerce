<?php

namespace Drupal\commerce\Plugin\Commerce\InlineForm;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines the interface for inline forms.
 *
 * Inline forms are embeddable and reusable.
 * They are used as an alternative to building complex custom form elements,
 * which have problems with rebuilding on #ajax due to being processed too
 * early. Unlike form elements, inline forms support dependency injection
 * and allow swapping out the implementing class through an alter hook.
 *
 * Just like form elements, inline forms are automatically validated and
 * submitted when the complete form is validated/submitted.
 */
interface InlineFormInterface extends ConfigurableInterface, PluginInspectionInterface {

  /**
   * Gets the inline form label.
   *
   * @return string
   *   The inline form label.
   */
  public function getLabel();

  /**
   * Builds the inline form.
   *
   * @param array $inline_form
   *   The inline form, containing the following basic properties:
   *   - #parents: Identifies the location of the field values in $form_state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the complete form.
   *
   * @return array
   *   The built inline form.
   */
  public function buildInlineForm(array $inline_form, FormStateInterface $form_state);

  /**
   * Validates the inline form.
   *
   * @param array $inline_form
   *   The inline form, containing the following basic properties:
   *   - #parents: Identifies the location of the field values in $form_state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the complete form.
   */
  public function validateInlineForm(array &$inline_form, FormStateInterface $form_state);

  /**
   * Submits the inline form.
   *
   * @param array $inline_form
   *   The inline form, containing the following basic properties:
   *   - #parents: Identifies the location of the field values in $form_state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the complete form.
   */
  public function submitInlineForm(array &$inline_form, FormStateInterface $form_state);

}
