<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Places an order through a series of steps.
 *
 * Checkout flows are multi-step forms that can be configured by the store
 * administrator. This configuration is stored in the commerce_checkout_flow
 * config entity and injected into the plugin at instantiation.
 */
interface CheckoutFlowInterface extends FormInterface, ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Gets the current order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The current order.
   */
  public function getOrder();

  /**
   * Gets the current step ID.
   *
   * @return string
   *   The current step ID.
   */
  public function getStepId();

  /**
   * Gets the previous step ID.
   *
   * Determined based on the position of the current step ID in the list
   * of visible steps.
   *
   * @return string|null
   *   The previous step, or NULL if there is none.
   */
  public function getPreviousStepId();

  /**
   * Gets the next step ID.
   *
   * Determined based on the position of the current step ID in the list
   * of visible steps.
   *
   * @return string|null
   *   The next step ID, or NULL if there is none.
   */
  public function getNextStepId();

  /**
   * Gets the defined steps.
   *
   * @return array
   *   An array of step definitions, keyed by step ID.
   *   Each step definition has the following keys:
   *   - label: The label of the step.
   *   - previous_label: The label shown on the button that returns the
   *                     customer back to this step.
   *   - next_label: The label shown on the button that sends the customer to
   *                 this step.
   *   If the previous_label or next_label keys are missing, the corresponding
   *   buttons will not be shown to the customer.
   */
  public function getSteps();

  /**
   * Gets the visible steps.
   *
   * @return array
   *   An array of step definitions, keyed by step ID.
   */
  public function getVisibleSteps();

  /**
   * Builds the order summary for the current checkout step.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildOrderSummary(array $form, FormStateInterface $form_state);

}
