<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for checkout panes.
 *
 * Checkout panes are configurable forms embedded into the checkout flow form.
 */
interface CheckoutPaneInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Sets the current order.
   *
   * Used to keep the pane order in sync with the checkout flow order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return $this
   */
  public function setOrder(OrderInterface $order);

  /**
   * Gets the pane ID.
   *
   * @return string
   *   The pane ID.
   */
  public function getId();

  /**
   * Gets the pane label.
   *
   * This label is admin-facing.
   *
   * @return string
   *   The pane label.
   */
  public function getLabel();

  /**
   * Gets the pane display label.
   *
   * This label is customer-facing.
   * Shown as the title of the pane form if the wrapper_element is 'fieldset'.
   *
   * @return string
   *   The pane display label.
   */
  public function getDisplayLabel();

  /**
   * Gets the pane wrapper element.
   *
   * Used when rendering the pane's form.
   * E.g: 'container', 'fieldset'. Defaults to 'container'.
   *
   * @return string
   *   The pane wrapper element.
   */
  public function getWrapperElement();

  /**
   * Gets the pane step ID.
   *
   * @return string
   *   The pane step ID.
   */
  public function getStepId();

  /**
   * Sets the pane step ID.
   *
   * @param string $step_id
   *   The pane step ID.
   *
   * @return $this
   */
  public function setStepId($step_id);

  /**
   * Gets the pane weight.
   *
   * @return string
   *   The pane weight.
   */
  public function getWeight();

  /**
   * Sets the pane weight.
   *
   * @param int $weight
   *   The pane weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Builds a summary of the pane configuration.
   *
   * Complements the methods provided by PluginFormInterface, allowing
   * the checkout flow form to provide a summary of pane configuration.
   *
   * @return string
   *   An HTML summary of the pane configuration.
   */
  public function buildConfigurationSummary();

  /**
   * Determines whether the pane is visible.
   *
   * @return bool
   *   TRUE if the pane is visible, FALSE otherwise.
   */
  public function isVisible();

  /**
   * Builds a summary of the pane values.
   *
   * Important:
   * The review pane shows summaries for both visible and non-visible panes.
   * To skip showing a summary for a non-visible pane, check isVisible()
   * and return an empty array.
   *
   * @return array
   *   A render array containing the summary of the pane values.
   */
  public function buildPaneSummary();

  /**
   * Builds the pane form.
   *
   * @param array $pane_form
   *   The pane form, containing the following basic properties:
   *   - #parents: Identifies the position of the pane form in the overall
   *     parent form, and identifies the location where the field values are
   *     placed within $form_state->getValues().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the parent form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form);

  /**
   * Validates the pane form.
   *
   * @param array $pane_form
   *   The pane form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the parent form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form);

  /**
   * Handles the submission of an pane form.
   *
   * @param array $pane_form
   *   The pane form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the parent form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form);

}
