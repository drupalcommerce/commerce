<?php

namespace Drupal\commerce_payment\PluginForm;

use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Provides the base class for payment off-site forms.
 *
 * Payment gateways are expected to inherit from this class and provide a
 * buildConfigurationForm() method which calls buildRedirectForm() with the
 * right parameters.
 */
abstract class PaymentOffsiteForm extends PaymentGatewayFormBase {

  // The redirect methods.
  const REDIRECT_GET = 'get';
  const REDIRECT_POST = 'post';

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if (empty($form['#return_url'])) {
      throw new \InvalidArgumentException('The offsite-payment form requires the #return_url property.');
    }
    if (empty($form['#cancel_url'])) {
      throw new \InvalidArgumentException('The offsite-payment form requires the #cancel_url property.');
    }
    if (!isset($form['#capture'])) {
      $form['#capture'] = TRUE;
    }

    return $form;
  }

  /**
   * Builds the redirect form.
   *
   * @param array $form
   *   The plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $redirect_url
   *   The redirect url.
   * @param array $data
   *   Data that should be sent along.
   * @param string $redirect_method
   *   The redirect method (REDIRECT_GET or REDIRECT_POST constant).
   *
   * @return array
   *   The redirect form, if $redirect_method is REDIRECT_POST.
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   *   The redirect exception, if $redirect_method is REDIRECT_GET.
   */
  protected function buildRedirectForm(array $form, FormStateInterface $form_state, $redirect_url, array $data, $redirect_method = self::REDIRECT_GET) {
    if ($redirect_method == self::REDIRECT_POST) {
      $form['#attached']['library'][] = 'commerce_payment/offsite_redirect';
      $form['#process'][] = [get_class($this), 'processRedirectForm'];
      $form['#redirect_url'] = $redirect_url;

      foreach ($data as $key => $value) {
        $form[$key] = [
          '#type' => 'hidden',
          '#value' => $value,
          // Ensure the correct keys by sending values from the form root.
          '#parents' => [$key],
        ];
      }
      // The key is prefixed with 'commerce_' to prevent conflicts with $data.
      $form['commerce_message'] = [
        '#markup' => '<div class="checkout-help">' . t('Please wait while you are redirected to the payment server. If nothing happens within 10 seconds, please click on the button below.') . '</div>',
        '#weight' => -10,
      ];
    }
    else {
      $redirect_url = Url::fromUri($redirect_url, ['absolute' => TRUE, 'query' => $data])->toString();
      throw new NeedsRedirectException($redirect_url);
    }

    return $form;
  }

  /**
   * Prepares the complete form for a POST redirect.
   *
   * Sets the form #action, adds a class for the JS to target.
   * Workaround for buildConfigurationForm() not receiving $complete_form.
   *
   * @param array $form
   *   The plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed form element.
   */
  public static function processRedirectForm(array $form, FormStateInterface $form_state, array &$complete_form) {
    $complete_form['#action'] = $form['#redirect_url'];
    $complete_form['#attributes']['class'][] = 'payment-redirect-form';
    // The form actions are hidden by default, but needed in this case.
    $complete_form['actions']['#access'] = TRUE;
    foreach (Element::children($complete_form['actions']) as $element_name) {
      $complete_form['actions'][$element_name]['#access'] = TRUE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Nothing. Off-site payment gateways do not submit forms to Drupal.
  }

}
