<?php

namespace Drupal\commerce_payment\Element;

use Drupal\commerce_payment\Entity\EntityWithPaymentGatewayInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a form element for embedding the payment gateway forms.
 *
 * Usage example:
 * @code
 * $form['payment_method'] = [
 *   '#type' => 'commerce_payment_gateway_form',
 *   '#operation' => 'add-payment-method',
 *   // A payment or payment method entity, depending on the operation.
 *   // On submit, the payment method will be created remotely, and the
 *   // entity updated, for access via $form_state->getValue('payment_method')
 *   '#default_value' => $payment_method,
 * ];
 * @endcode
 *
 * @RenderElement("commerce_payment_gateway_form")
 */
class PaymentGatewayForm extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#operation' => '',
      // The entity operated on. Instance of EntityWithPaymentGatewayInterface.
      '#default_value' => NULL,
      '#process' => [
        [$class, 'processForm'],
      ],
      '#element_validate' => [
        [$class, 'validateForm'],
      ],
      '#element_submit' => [
        [$class, 'submitForm'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Builds the payment gateway form.
   *
   * @param array $element
   *   The form element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the #operation or #default_value properties are empty, or
   *   when the #default_value property is not a valid entity.
   *
   * @return array
   *   The processed form element.
   */
  public static function processForm($element, FormStateInterface $form_state, &$complete_form) {
    if (empty($element['#operation'])) {
      throw new \InvalidArgumentException('The commerce_payment_gateway_form element requires the #operation property.');
    }
    if (empty($element['#default_value'])) {
      throw new \InvalidArgumentException('The commerce_payment_gateway_form element requires the #default_value property.');
    }
    elseif (isset($element['#default_value']) && !($element['#default_value'] instanceof EntityWithPaymentGatewayInterface)) {
      throw new \InvalidArgumentException('The commerce_payment_gateway_form #default_value property must be a payment or a payment method entity.');
    }
    $plugin_form = static::createPluginForm($element);
    $element = $plugin_form->buildConfigurationForm($element, $form_state);
    // Allow the plugin form to override the page title.
    if (isset($element['#page_title'])) {
      $complete_form['#title'] = $element['#page_title'];
    }
    // The #validate callbacks of the complete form run last.
    // That allows executeElementSubmitHandlers() to be completely certain that
    // the form has passed validation before proceeding.
    $complete_form['#validate'][] = [get_class(), 'executeElementSubmitHandlers'];

    return $element;
  }

  /**
   * Validates the payment gateway form.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Exception
   *   Thrown if button-level #validate handlers are detected on the parent
   *   form, as a protection against buggy behavior.
   */
  public static function validateForm(&$element, FormStateInterface $form_state) {
    // Button-level #validate handlers replace the form-level ones, which means
    // that executeElementSubmitHandlers() won't be triggered.
    if ($handlers = $form_state->getValidateHandlers()) {
      throw new \Exception('The commerce_payment_gateway_form element is not compatible with submit buttons that set #validate handlers');
    }

    $plugin_form = self::createPluginForm($element);
    $plugin_form->validateConfigurationForm($element, $form_state);
  }

  /**
   * Submits the payment gateway form.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitForm(&$element, FormStateInterface $form_state) {
    $plugin_form = self::createPluginForm($element);

    try {
      $plugin_form->submitConfigurationForm($element, $form_state);
      $form_state->setValueForElement($element, $plugin_form->getEntity());
    }
    catch (PaymentGatewayException $e) {
      $error_element = $plugin_form->getErrorElement($element, $form_state);
      $form_state->setError($error_element, $e->getMessage());
    }
  }

  /**
   * Creates an instance of the plugin form.
   *
   * @param array $element
   *   The form element.
   *
   * @return \Drupal\commerce_payment\PluginForm\PaymentGatewayFormInterface
   *   The plugin form.
   */
  public static function createPluginForm($element) {
    /** @var \Drupal\commerce\PluginForm\PluginFormFactoryInterface $plugin_form_factory */
    $plugin_form_factory = \Drupal::service('plugin_form.factory');
    /** @var \Drupal\commerce_payment\Entity\EntityWithPaymentGatewayInterface $entity */
    $entity = $element['#default_value'];
    $plugin = $entity->getPaymentGateway()->getPlugin();
    /** @var \Drupal\commerce_payment\PluginForm\PaymentGatewayFormInterface $plugin_form */
    $plugin_form = $plugin_form_factory->createInstance($plugin, $element['#operation']);
    $plugin_form->setEntity($entity);

    return $plugin_form;
  }

  /**
   * Submits elements by calling their #element_submit callbacks.
   *
   * Form API has no #element_submit, requiring us to simulate it by running
   * our #element_submit handlers either in the last step of validation, or the
   * first step of submission. In this case it's the last step of validation,
   * allowing exceptions thrown by the plugin to be converted into form errors.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function executeElementSubmitHandlers(&$element, FormStateInterface $form_state) {
    if (!$form_state->isSubmitted() || $form_state->hasAnyErrors()) {
      // The form wasn't submitted (#ajax in progress) or failed validation.
      return;
    }

    // Recurse through all children.
    foreach (Element::children($element) as $key) {
      if (!empty($element[$key])) {
        static::executeElementSubmitHandlers($element[$key], $form_state);
      }
    }

    // If there are callbacks on this level, run them.
    if (!empty($element['#element_submit'])) {
      foreach ($element['#element_submit'] as $callback) {
        call_user_func_array($callback, [&$element, &$form_state]);
      }
    }
  }

}
