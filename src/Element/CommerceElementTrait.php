<?php

namespace Drupal\commerce\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a trait for Commerce form elements.
 *
 * Allows form elements to use #commerce_element_submit, a substitute
 * for the #element_submit that's missing from Drupal core.
 *
 * Each form element using this trait should add the attachElementSubmit and
 * validateElementSubmit callbacks to their getInfo() methods.
 */
trait CommerceElementTrait {

  /**
   * Attaches the #commerce_element_submit functionality.
   *
   * @param array $element
   *   The form element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed form element.
   */
  public static function attachElementSubmit(array $element, FormStateInterface $form_state, array &$complete_form) {
    if (isset($complete_form['#commerce_element_submit_attached'])) {
      return $element;
    }
    // The #validate callbacks of the complete form run last.
    // That allows executeElementSubmitHandlers() to be completely certain that
    // the form has passed validation before proceeding.
    $complete_form['#validate'][] = [get_class(), 'executeElementSubmitHandlers'];
    $complete_form['#commerce_element_submit_attached'] = TRUE;

    return $element;
  }

  /**
   * Confirms that #commerce_element_submit handlers can be run.
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
  public static function validateElementSubmit(array &$element, FormStateInterface $form_state) {
    // Button-level #validate handlers replace the form-level ones, which means
    // that executeElementSubmitHandlers() won't be triggered.
    if ($handlers = $form_state->getValidateHandlers()) {
      throw new \Exception('The current form must not have button-level #validate handlers');
    }
  }

  /**
   * Submits elements by calling their #commerce_element_submit callbacks.
   *
   * Form API has no #element_submit, requiring us to simulate it by running
   * the #commerce_element_submit handlers either in the last step of
   * validation, or the first step of submission. In this case it's the last
   * step of validation, allowing thrown exceptions to be converted into form
   * errors.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function executeElementSubmitHandlers(array &$element, FormStateInterface $form_state) {
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
    if (!empty($element['#commerce_element_submit'])) {
      foreach ($element['#commerce_element_submit'] as $callback) {
        call_user_func_array($callback, [&$element, &$form_state]);
      }
    }
  }

}
