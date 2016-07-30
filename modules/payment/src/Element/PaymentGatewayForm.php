<?php

namespace Drupal\commerce_payment\Element;

use Drupal\commerce_payment\Entity\EntityWithPaymentGatewayInterface;
use Drupal\Core\Form\FormStateInterface;
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
      // The entity operated on.
      // Must implement EntityWithPaymentGatewayInterface.
      '#default_value' => NULL,
      '#process' => [
        [$class, 'processForm'],
      ],
      '#element_validate' => [
        [$class, 'validateForm'],
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

    return $element;
  }

  /**
   * Validates the payment gateway form.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateForm(&$element, FormStateInterface $form_state) {
    if ($form_state->hasAnyErrors()) {
      // A different part of the parent form has already failed validation.
      return;
    }

    // Payment gateway methods might throw exceptions which require the plugin
    // form to be rebuilt in order to show the error to the user. That only
    // works during form validation, so we call submitConfigurationForm() here.
    $plugin_form = self::createPluginForm($element);
    $plugin_form->validateConfigurationForm($element, $form_state);
    if (!$form_state->hasAnyErrors()) {
      // Proceed to submission only if validation didn't trigger any errors.
      $plugin_form->submitConfigurationForm($element, $form_state);
      $form_state->setValueForElement($element, $plugin_form->getEntity());
    }
  }

  /**
   * Creates an instance of the plugin form.
   *
   * @param array $element
   *   The form element.
   *
   * @return \Drupal\commerce\PluginForm\PluginEntityFormInterface
   *   The plugin form.
   */
  public static function createPluginForm($element) {
    /** @var \Drupal\commerce\PluginForm\PluginFormFactoryInterface $plugin_form_factory */
    $plugin_form_factory = \Drupal::service('commerce.plugin_form_factory');
    /** @var \Drupal\commerce_payment\Entity\EntityWithPaymentGatewayInterface */
    $entity = $element['#default_value'];
    $plugin = $entity->getPaymentGateway()->getPlugin();
    /** @var \Drupal\commerce\PluginForm\PluginEntityFormInterface $plugin_form */
    $plugin_form = $plugin_form_factory->createInstance($plugin, $element['#operation']);
    $plugin_form->setEntity($entity);

    return $plugin_form;
  }

}
