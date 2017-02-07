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
use Drupal\profile\Entity\Profile;
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

    $element['#profile'] = $element['#default_value'];
    $bundle = $element['#profile']->bundle();
    $default_profile_id = $element['#profile']->id();
    $default_profile = $element['#profile'];
    // Fetch all profiles of the user for an addressbook functionality.
    $profile_uid = $element['#profile']->getOwnerId();
    // Anonymous users don't get an addressbook.
    if ($profile_uid) {
      $profile_ids = \Drupal::service('entity.query')
        ->get('profile')
        ->condition('uid', $profile_uid)
        ->condition('type', $bundle)
        ->condition('status', TRUE)
        ->sort('profile_id', 'DESC')
        ->execute();
      $profiles = Profile::loadMultiple($profile_ids);
      $profile_options = [];
      /** @var \Drupal\profile\Entity\Profile $profile_option */
      foreach ($profiles as $profile_option) {
        if (empty($default_profile_id)) {
          $default_profile_id = $profile_option->id();
          $default_profile = $profile_option;
        }
        $profile_options[$profile_option->id()] = $profile_option->label();
      }
      $profile_options['new_profile'] = t('+ Enter a new profile');
    }
    $mode = 'view';
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element)) {
      $triggering_element_parents = $triggering_element['#parents'];
      $last_parent = array_pop($triggering_element_parents);
      if ($triggering_element_parents == $element['#parents']) {
        if ($last_parent == 'edit_button') {
          $mode = 'edit';
        }
        elseif ($last_parent == 'cancel_button') {
          $mode = 'view';
        }
        elseif ($last_parent == 'profile_selection' && $triggering_element['#value'] == 'new_profile') {
          $mode = 'new';
        }
      }
    }
    else {
      if (empty($profile_ids)) {
        $mode = 'new';
      }
    }

    $ajax_wrapper_id = Html::getUniqueId('profile-select-ajax-wrapper');
    // Prefix and suffix used for Ajax replacement.
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $element['#suffix'] = '</div>';

    // No profiles found or user wants to create a new one.
    if ($mode == 'new') {
      $values = [
        'type' => $element['#profile']->bundle(),
        'uid' => $element['#profile']->getOwnerId(),
      ];
      $default_profile = Profile::create($values);
      $default_profile_id = NULL;
    }

    $form_display = EntityFormDisplay::collectRenderDisplay($default_profile, 'default');
    $form_display->buildForm($default_profile, $element, $form_state);
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
    if (!empty($profile_uid) && $mode != 'edit' && !empty($profile_options) && count($profile_options) > 1) {
      $element['profile_selection'] = [
        '#title' => t('Select a profile'),
        '#options' => $profile_options,
        '#type' => 'select',
        '#weight' => -5,
        '#default_value' => $default_profile_id,
        '#ajax' => [
          'callback' => [$called_class, 'profileSelectAjax'],
          'wrapper' => $ajax_wrapper_id,
        ],
      ];
    }

    $name = array_shift($element['#parents']);
    $element['cancel_button'] = [
      '#type' => 'button',
      '#value' => t('Return to profile selection'),
      '#limit_validation_errors' => [],
      '#name' => $name . '[' . implode('][', $element['#parents']) . '][cancel_button]',
      '#ajax' => [
        'callback' => [$called_class, 'profileSelectAjax'],
        'wrapper' => $ajax_wrapper_id,
      ],
      '#weight' => 99,
    ];
    array_unshift($element['#parents'], $name);

    // Viewing a profile.
    if ($mode == 'view' ) {
      $view_builder = \Drupal::entityTypeManager()
        ->getViewBuilder('profile');
      $content = $view_builder->view($default_profile, 'default');

      $element['rendered_profile'] = [
        $content,
      ];

      $name = array_shift($element['#parents']);
      $element['edit_button'] = [
        '#type' => 'button',
        '#value' => t('Edit'),
        '#name' => $name . '[' . implode('][', $element['#parents']) . '][edit_button]',
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [$called_class, 'profileSelectAjax'],
          'wrapper' => $ajax_wrapper_id,
        ],
      ];
      array_unshift($element['#parents'], $name);

      foreach (Element::children($element) as $key) {
        if (!in_array($key, ['edit_button', 'rendered_profile', 'profile_selection'])) {
          hide($element[$key]);
        }
      }
    }
    if (empty($profiles) || $mode != 'edit') {
      hide($element['cancel_button']);
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
    $triggering_parents = $form_state->getTriggeringElement()['#parents'];
    $triggering_last_parent = array_pop($triggering_parents);
    if (!in_array($triggering_last_parent, ['edit_button', 'cancel_button', 'profile_selection', 'country_code'])) {
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
    $form_display = EntityFormDisplay::collectRenderDisplay($element['#profile'], 'default');
    $form_display->extractFormValues($element['#profile'], $element, $form_state);
    $element['#profile']->save();
  }

  /**
   * Profile form AJAX callback.
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
  public static function profileSelectAjax(array &$form, FormStateInterface $form_state, Request $request) {
    $triggering_parents = $form_state->getTriggeringElement()['#parents'];
    array_pop($triggering_parents);
    return NestedArray::getValue($form, $triggering_parents);
  }
}
