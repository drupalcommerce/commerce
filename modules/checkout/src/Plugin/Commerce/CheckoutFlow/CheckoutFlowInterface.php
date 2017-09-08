<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Places an order through a series of steps.
 *
 * Checkout flows are multi-step forms that can be configured by the store
 * administrator. This configuration is stored in the commerce_checkout_flow
 * config entity and injected into the plugin at instantiation.
 */
interface CheckoutFlowInterface extends FormInterface, BaseFormIdInterface, ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Gets the current order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The current order.
   */
  public function getOrder();

  /**
   * Gets the previous step ID for the given step ID.
   *
   * @param string $step_id
   *   The step ID.
   *
   * @return string|null
   *   The previous step, or NULL if there is none.
   */
  public function getPreviousStepId($step_id);

  /**
   * Gets the next step ID for the given step ID.
   *
   * @param string $step_id
   *   The step ID.
   *
   * @return string|null
   *   The next step ID, or NULL if there is none.
   */
  public function getNextStepId($step_id);

  /**
   * Redirects an order to a specific step in the checkout.
   *
   * @param string $step_id
   *   The step ID to redirect to.
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function redirectToStep($step_id);

  /**
   * Gets the defined steps.
   *
   * @return array
   *   An array of step definitions, keyed by step ID.
   *   Possible keys:
   *   - label: The label of the step.
   *   - previous_label: The label shown on the button that returns the
   *                     customer back to this step.
   *   - next_label: The label shown on the button that sends the customer to
   *                 this step.
   *   - has_sidebar: Whether the step has a sidebar.
   *   - hidden: Whether the step is hidden. Hidden steps aren't shown
   *             in the checkout progress block until they are reached.
   *   Note:
   *   If the previous_label or next_label keys are missing, the
   *   corresponding buttons will not be shown to the customer.
   */
  public function getSteps();

  /**
   * Gets the visible steps.
   *
   * @return array
   *   An array of step definitions, keyed by step ID.
   */
  public function getVisibleSteps();

}
