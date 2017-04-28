<?php

namespace Drupal\commerce_order\Element;

use Drupal\commerce\Element\CommerceElementTrait;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\profile\Entity\ProfileInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a form element for selecting a customer profile.
 *
 * Usage example:
 * @code
 * $form['billing_profile'] = [
 *   '#type' => 'commerce_profile_select',
 *   '#default_value' => $profile,
 *   '#default_country' => 'FR',
 *   '#available_countries' => ['US', 'FR'],
 *   '#reuse_profile_label' => $this->t('My billing address is the same as my shipping address.'),
 *   '#reuse_profile_source' => 'commerce_shipping_get_shipping_profile',
 *   '#reuse_profile_default' => FALSE,
 * ];
 * @endcode
 * To access the profile in validation or submission callbacks, use
 * $form['billing_profile']['#profile']. Due to Drupal core limitations the
 * profile can't be accessed via $form_state->getValue('billing_profile').
 *
 * @RenderElement("commerce_profile_select")
 */
class ProfileSelect extends RenderElement {

  use CommerceElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      // The country to select if the address widget doesn't have a default.
      '#default_country' => NULL,
      // A list of country codes. If empty, all countries will be available.
      '#available_countries' => [],
      // The label for the reuse profile checkbox. If empty, checkbox is hidden.
      '#reuse_profile_label' => NULL,
      // The function to call to return the profile to reuse.
      '#reuse_profile_source' => NULL,
      // Whether the reuse checkbox should be checked by default.
      '#reuse_profile_default' => FALSE,

      // The profile entity operated on. Required.
      '#default_value' => NULL,
      '#process' => [
        [$class, 'attachElementSubmit'],
        [$class, 'processForm'],
      ],
      '#element_validate' => [
        [$class, 'validateElementSubmit'],
        [$class, 'validateForm'],
      ],
      '#commerce_element_submit' => [
        [$class, 'submitForm'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Builds the element form.
   *
   * @param array $element
   *   The form element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @throws \InvalidArgumentException
   *   Thrown when #default_value is empty or not an entity, or when
   *   #available_countries is not an array of country codes.
   *
   * @return array
   *   The processed form element.
   */
  public static function processForm(array $element, FormStateInterface $form_state, array &$complete_form) {
    if (empty($element['#default_value'])) {
      throw new \InvalidArgumentException('The commerce_profile_select element requires the #default_value property.');
    }
    elseif (isset($element['#default_value']) && !($element['#default_value'] instanceof ProfileInterface)) {
      throw new \InvalidArgumentException('The commerce_profile_select #default_value property must be a profile entity.');
    }
    if (!is_array($element['#available_countries'])) {
      throw new \InvalidArgumentException('The commerce_profile_select #available_countries property must be an array.');
    }

    // Assign a name if needed.
    if (empty($element['#name'])) {
      list($name) = explode('--', $element['#id']);
      $element['#name'] = 'profile-select--' . $name;
    }

    $ajax_wrapper_id = Html::getUniqueId('profile-select-ajax-wrapper');
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $element['#suffix'] = '</div>';

    $storage = $form_state->getStorage();
    $element['#profile'] = $element['#default_value'];
    $reuse_profile = (isset($storage['pane_' . $element['#name']]['reuse_profile']))
      ? $storage['pane_' . $element['#name']]['reuse_profile']
      : $element['#reuse_profile_default'];
    $storage['pane_' . $element['#name']]['reuse_profile'] = $reuse_profile;
    $form_state->setStorage($storage);

    $form_display = EntityFormDisplay::collectRenderDisplay($element['#profile'], 'default');
    $form_display->buildForm($element['#profile'], $element, $form_state);
    if (!empty($element['address']['widget'][0])) {
      $widget_element = &$element['address']['widget'][0];
      // Remove the details wrapper from the address widget.
      $widget_element['#type'] = 'container';
      // Provide a default country.
      if (!empty($element['#default_country']) && empty($widget_element['address']['#default_value']['country_code'])) {
        $widget_element['address']['#default_value']['country_code'] = $element['#default_country'];
      }
      // Limit the available countries.
      if (!empty($element['#available_countries'])) {
        $widget_element['address']['#available_countries'] = $element['#available_countries'];
      }
    }

    $called_class = get_called_class();
    $reuse_enabled = (!empty($element['#reuse_profile_label']) && !empty($element['#reuse_profile_source']));
    if ($reuse_enabled) {
      $element['reuse_profile'] = [
        '#title' => $element['#reuse_profile_label'],
        '#type' => 'checkbox',
        '#weight' => -5,
        '#default_value' => $reuse_profile,
        '#ajax' => [
          'callback' => [$called_class, 'reuseProfileAjax'],
          'wrapper' => $ajax_wrapper_id,
        ],
        '#element_validate' => [[$called_class, 'reuseProfileValidate']]
      ];
    }

    if ($reuse_profile) {
      foreach (Element::children($element) as $key) {
        if (!in_array($key, ['reuse_profile'])) {
          $element[$key]['#access'] = FALSE;
        }
      }
    }

    return $element;
  }

  /**
   * Validates the element form.
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
  public static function validateForm(array &$element, FormStateInterface $form_state) {
    $pane_id = $element['#name'];
    $storage = $form_state->getStorage();
    if (!isset($storage['pane_' . $pane_id]['reuse_profile']) || !$storage['pane_' . $pane_id]['reuse_profile']) {
      $form_display = EntityFormDisplay::collectRenderDisplay($element['#profile'], 'default');
      $form_display->extractFormValues($element['#profile'], $element, $form_state);
      $form_display->validateFormValues($element['#profile'], $element, $form_state);
    }
  }

  /**
   * Submits the element form.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitForm(array &$element, FormStateInterface $form_state) {
    $pane_id = $element['#name'];
    $storage = $form_state->getStorage();
    if (isset($storage['pane_' . $pane_id]['reuse_profile']) && $storage['pane_' . $pane_id]['reuse_profile']) {
      if (is_numeric($element['#reuse_profile_source'])) {
        // Load profile by ID
        $profile = \Drupal::entityTypeManager()
          ->getStorage('profile')
          ->load($element['#reuse_profile_source']);
      }
      else {
        // Load profile from a callback
        $profile = call_user_func($element['#reuse_profile_source'], $element, $form_state, $form_state->getCompleteForm());
      }

      $element['#profile'] = $profile;
    } else {
      $form_display = EntityFormDisplay::collectRenderDisplay($element['#profile'], 'default');
      $form_display->extractFormValues($element['#profile'], $element, $form_state);
      $element['#profile']->save();
    }
  }

  /**
   * Reuse profile AJAX callback.
   *
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request object.
   *
   * @return array
   *   The form element replace the wrapper with.
   */
  public static function reuseProfileAjax(array &$form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $array_parents = $triggering_element['#array_parents'];
    array_pop($array_parents);
    return NestedArray::getValue($form, $array_parents);
  }

  /**
   * The #element_validate callback for the reuse profile checkbox.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function reuseProfileValidate(array $element, FormStateInterface $form_state) {
    $form = $form_state->getCompleteForm();
    $profile_element_parents = $element['#parents'];
    array_pop($profile_element_parents);
    $profile_element = NestedArray::getValue($form, $profile_element_parents);
    $pane_id = $profile_element['#name'];
    $storage = $form_state->getStorage();
    $storage['pane_' . $pane_id]['reuse_profile'] = $element['#value'];
    $form_state->setStorage($storage);
  }

}
