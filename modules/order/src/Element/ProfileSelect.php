<?php

namespace Drupal\commerce_order\Element;

use Drupal\commerce\Element\CommerceElementBase;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
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
class ProfileSelect extends CommerceElementBase {

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
      '#after_build' => [
        [$class, 'afterBuild'],
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

    // Initialize variables.
    $element['#profile'] = $element['#default_value'];
    $bundle = $element['#default_value']->bundle();
    $default_profile_id = NULL;
    // Fetch all profiles of the user for an addressbook functionality.
    $profile_uid = $element['#default_value']->getOwnerId();
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

    $pane_id = $element['#name'];
    $mode = 'view';
    $profile_selection_parents = $element['#parents'];
    $profile_selection_parents[] = 'profile_selection';
    $profile_id = $form_state->getValue($profile_selection_parents);
    $storage = $form_state->getStorage();
    $reuse_profile = (isset($storage['pane_' . $pane_id]['reuse_profile']))
      ? $storage['pane_' . $pane_id]['reuse_profile']
      : $element['#reuse_profile_default'];

    // User is adding a new profile.
    if ($profile_id && $profile_id == 'new_profile') {
      $mode = 'new';
      unset($storage['pane_' . $pane_id]);
    }
    // If an AJAX rebuild happened, we might have our data in form state.
    elseif (!empty($storage['pane_' . $pane_id]['profile'])) {
      $default_profile = $storage['pane_' . $pane_id]['profile'];
      $default_profile_id = $default_profile->id();
      $mode = $storage['pane_' . $pane_id]['mode'];
      $bundle = $default_profile->bundle();
    }
    // Loading the page for the first time.
    elseif ($element['#default_value']->id()) {
      $default_profile = $element['#default_value'];
      $default_profile_id = $default_profile->id();
      $bundle = $default_profile->bundle();
    }

    $ajax_wrapper_id = Html::getUniqueId('profile-select-ajax-wrapper');
    // Prefix and suffix used for Ajax replacement.
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $element['#suffix'] = '</div>';

    // Remember the current profile and mode in form state.
    if (!empty($default_profile)) {
      $storage['pane_' . $pane_id] = [
        'profile' => $default_profile,
        'mode' => $mode,
      ];
      $element['#default_value'] = $default_profile;
    }
    // No profiles found or user wants to create a new one.
    elseif (empty($profiles) || $mode == 'new') {
      $values = [
        'type' => $element['#default_value']->bundle(),
        'uid' => $element['#default_value']->getOwnerId(),
      ];
      $default_profile = Profile::create($values);
      $mode = 'new';
    }

    $form_display = EntityFormDisplay::collectRenderDisplay($element['#default_value'], 'default');
    $form_display->buildForm($element['#default_value'], $element, $form_state);
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

    // Maintain the state of the Reuse Profile checkbox
    $storage['pane_' . $pane_id]['reuse_profile'] = $reuse_profile;
    $form_state->setStorage($storage);

    $called_class = get_called_class();
    $reuse_enabled = (!empty($element['#reuse_profile_label']) && !empty($element['#reuse_profile_source']));
    if ($reuse_enabled) {
      $element['reuse_profile'] = [
        '#title' => $element['#reuse_profile_label'],
        '#type' => 'checkbox',
        '#weight' => -5,
        '#default_value' => $reuse_profile,
        '#ajax' => [
          'callback' => [$called_class, 'profileAjax'],
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
    } else {
      if (!empty($profile_uid) && $mode != 'edit' && !empty($profile_options) && count($profile_options) > 1) {
        $element['profile_selection'] = [
          '#title' => t('Select a profile'),
          '#options' => $profile_options,
          '#type' => 'select',
          '#weight' => -5,
          '#default_value' => $default_profile_id,
          '#ajax' => [
            'callback' => [$called_class, 'profileAjax'],
            'wrapper' => $ajax_wrapper_id,
          ],
          '#element_validate' => [[$called_class, 'profileSelectValidate']],
        ];
      }

      // Viewing a profile.
      if ($mode == 'view') {
        $view_builder = \Drupal::entityTypeManager()
          ->getViewBuilder('profile');
        $content = $view_builder->view($default_profile, 'default');

        $element['rendered_profile'] = [
          $content,
        ];
        $element['edit_button'] = [
          '#type' => 'button',
          '#name' => 'pane-' . $pane_id . '-edit',
          '#value' => t('Edit'),
          '#limit_validation_errors' => [],
          '#ajax' => [
            'callback' => [$called_class, 'profileAjax'],
            'wrapper' => $ajax_wrapper_id,
          ],
          '#element_validate' => [[$called_class, 'profileEditCancelValidate']],
        ];
        foreach (Element::children($element) as $key) {
          if (!in_array($key, ['edit_button', 'rendered_profile', 'profile_selection', 'reuse_profile'])) {
            $element[$key]['#access'] = FALSE;
          }
        }
      }
      // Add the field widgets for an existing profile.
      elseif (!empty($profiles) && $mode == 'edit') {
        $element['cancel_button'] = [
          '#type' => 'button',
          '#name' => 'pane-' . $pane_id . '-cancel',
          '#value' => t('Return to profile selection'),
          '#limit_validation_errors' => [],
          '#ajax' => [
            'callback' => [$called_class, 'profileAjax'],
            'wrapper' => $ajax_wrapper_id,
          ],
          '#element_validate' => [[$called_class, 'profileEditCancelValidate']],
          '#weight' => 99,
        ];
      }
    }

    return $element;
  }

  /**
   * Element after_build callback.
   *
   * @param array $element
   *   The form element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The built form element.
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element) {
      $parents = $triggering_element['#parents'];
      $last_parent = array_pop($parents);
      if ($last_parent == 'edit_button') {
        foreach (Element::children($element) as $key) {
          if (in_array($key, ['edit_button', 'rendered_profile', 'profile_selection', 'reuse_profile'])) {
            $element[$key]['#access'] = FALSE;
          }
          else {
            $element[$key]['#access'] = TRUE;
          }
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
      $parents = $form_state->getTriggeringElement()['#parents'];
      $triggering_last_parent = array_pop($parents);
      if (!in_array($triggering_last_parent, ['edit_button', 'cancel_button'])) {
        $form_display = EntityFormDisplay::collectRenderDisplay($element['#profile'], 'default');
        $form_display->extractFormValues($element['#profile'], $element, $form_state);
        $form_display->validateFormValues($element['#profile'], $element, $form_state);
      }
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
        $profile = \Drupal::entityTypeManager()->getStorage('profile')->load($element['#reuse_profile_source']);
      } else {
        // Load profile from a callback
        $profile = call_user_func($element['#reuse_profile_source'], $element, $form_state, $form_state->getCompleteForm());
      }
      $element['#profile'] = $profile;
    } elseif (isset($storage['pane_' . $pane_id]) && in_array($storage['pane_' . $pane_id]['mode'], ['new', 'edit'])) {
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
  public static function profileAjax(array &$form, FormStateInterface $form_state, Request $request) {
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

  /**
   * The #element_validate callback for the profiles dropdown select.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function profileSelectValidate(array $element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (in_array('profile_selection', $triggering_element['#array_parents']) && $triggering_element['#id'] == $element['#id']) {
      $form = $form_state->getCompleteForm();
      $profile_element_parents = $element['#parents'];
      array_pop($profile_element_parents);
      $profile_element = NestedArray::getValue($form, $profile_element_parents);
      $pane_id = $profile_element['#name'];
      $storage = $form_state->getStorage();

      // If the user wants to enter a new profile.
      if ($element['#value'] == 'new_profile') {
        $storage['pane_' . $pane_id]['mode'] = 'new';
        $values = [
          'type' => $profile_element['#profile']->bundle(),
          'uid' => $profile_element['#profile']->getOwnerId(),
        ];
        $profile = Profile::create($values);
        $storage['pane_' . $pane_id]['profile'] = $profile;
      }
      else {
        $storage['pane_' . $pane_id]['mode'] = 'view';
        $profile_id = $form_state->getValue($element['#parents']);
        $storage['pane_' . $pane_id]['profile'] = Profile::load($profile_id);
      }
      $form_state->setStorage($storage);
    }
  }

  /**
   * The #element_validate callback for the edit and cancel buttons.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function profileEditCancelValidate(array &$element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element && $triggering_element['#id'] === $element['#id']) {
      $array_parents = $triggering_element['#array_parents'];
      $last_parent = array_pop($array_parents);
      if (in_array($last_parent, ['edit_button', 'cancel_button'])) {
        $complete_form = $form_state->getCompleteForm();
        $parents = $element['#parents'];
        array_pop($parents);
        $element = NestedArray::getValue($complete_form, $parents);
        $pane_id = $element['#name'];
        $storage = $form_state->getStorage();
        if ($last_parent == 'edit_button') {
          $storage['pane_' . $pane_id]['mode'] = 'edit';
        }
        else {
          unset($storage['pane_' . $pane_id]);
        }
        $form_state->setStorage($storage);
      }
    }
  }

}
