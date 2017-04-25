<?php

namespace Drupal\commerce_order\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\commerce\Element\CommerceElementTrait;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityStorageException;
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
 *   '#profile_view_mode' => 'default',
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
      // The view mode to render existing profiles with.
      '#profile_view_mode' => 'default',
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

    // Assign a name if it's unset.
    if (empty($element['#name'])) {
      list($name) = explode('--', $element['#id']);
      $element['#name'] = 'profile-select--' . $name;
    }

    // Initialize variables.
    $ajax_wrapper_id = Html::getUniqueId('profile-select-ajax-wrapper');
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $element['#suffix'] = '</div>';
    $called_class = get_called_class();
    $pane_storage = self::getPaneStorage($element, $form_state);
    $profiles = self::getExistingProfiles($element['#default_value']);
    $mode = self::getMode($element, $form_state, $profiles);
    $default_profile = self::getDefaultProfile($element, $form_state, $mode, $profiles);
    $selected_profile = self::getProfileSelectValue($element, $form_state);
    $element['#default_value'] = $default_profile;
    $element['#profile'] = $default_profile;
    // Set up profile reuse functionality.
    $reuse_profile = (isset($pane_storage['reuse_profile']))
      ? $pane_storage['reuse_profile']
      : $element['#reuse_profile_default'];

    // Set default address field options.
    $form_display = EntityFormDisplay::collectRenderDisplay($default_profile, 'default');
    $form_display->buildForm($default_profile, $element, $form_state);
    if (!empty($element['address']['widget'][0])) {
      $widget_element = &$element['address']['widget'][0];
      // Remove the details wrapper from the address widget.
      $widget_element['#type'] = 'container';
      // Provide a default country.
      if (!empty($element['#default_country']) && $mode === 'new') {
        $widget_element['address']['#default_value']['country_code'] = $element['#default_country'];
      }
      // Limit the available countries.
      if (!empty($element['#available_countries'])) {
        $widget_element['address']['#available_countries'] = $element['#available_countries'];
      }
    }

    // Show the profile reuse checkbox if enabled.
    if ((!empty($element['#reuse_profile_label']) && !empty($element['#reuse_profile_source']))) {
      $element['reuse_profile'] = [
        '#title' => $element['#reuse_profile_label'],
        '#type' => 'checkbox',
        '#weight' => -5,
        '#default_value' => $reuse_profile,
        '#ajax' => [
          'callback' => [$called_class, 'profileAjax'],
          'wrapper' => $ajax_wrapper_id,
        ],
        '#element_validate' => [[$called_class, 'profileReuseValidate']]
      ];
    }

    // Hide the profile fields if profile reuse checkbox is checked.
    if ($reuse_profile) {
      self::hideProfileFields($element, ['reuse_profile']);
    }
    else {
      // Output a profile select element.
      if ($mode != 'edit' && !empty($profiles)) {
        $profile_options = [];
        foreach ($profiles as $profile_option) {
          $profile_options[$profile_option->id()] = $profile_option->label();
        }
        $profile_options['new_profile'] = t('+ Enter a new profile');

        $element['profile_selection'] = [
          '#title' => t('Select a profile'),
          '#options' => $profile_options,
          '#type' => 'select',
          '#weight' => -5,
          '#default_value' => $default_profile->id() ?: 'new_profile',
          '#ajax' => [
            'callback' => [$called_class, 'profileAjax'],
            'wrapper' => $ajax_wrapper_id,
          ],
          '#element_validate' => [[$called_class, 'profileSelectValidate']],
        ];
      }

      if ($mode == 'view') {
        $element['rendered_profile'] = [
          \Drupal::entityTypeManager()
            ->getViewBuilder('profile')
            ->view($default_profile, $element['#profile_view_mode']),
        ];
        $element['edit_button'] = [
          '#type' => 'button',
          '#name' => 'pane-' . $element['#name'] . '-edit',
          '#value' => t('Edit'),
          '#limit_validation_errors' => [],
          '#ajax' => [
            'callback' => [$called_class, 'profileAjax'],
            'wrapper' => $ajax_wrapper_id,
          ],
          '#element_validate' => [[$called_class, 'profileEditCancelValidate']],
        ];
        self::hideProfileFields($element, ['edit_button', 'rendered_profile', 'profile_selection', 'reuse_profile']);
      }
      // Editing an existing profile.
      elseif (!empty($profiles) && $mode == 'edit') {
        $element['cancel_button'] = [
          '#type' => 'button',
          '#name' => 'pane-' . $element['#name'] . '-cancel',
          '#value' => t('Cancel and return to profile selection'),
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

    // Maintain the state of the settings before returning.
    self::setPaneStorage($element, $form_state, [
      'profile' => $default_profile,
      'mode' => $mode,
      'reuse_profile' => $reuse_profile,
      'selected_profile' => $selected_profile,
    ]);
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
    if (!empty($triggering_element['#parents'])) {
      $last_parent = array_pop($triggering_element['#parents']);
      if ($last_parent == 'edit_button') {
        $retain = ['edit_button', 'rendered_profile', 'profile_selection', 'reuse_profile'];
        self::hideProfileFields($element, $retain, TRUE);
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
    $parents = $form_state->getTriggeringElement()['#parents'];
    $last_parent = array_pop($parents);
    $pane_storage = self::getPaneStorage($element, $form_state);
    if (!$pane_storage['reuse_profile'] && !in_array($last_parent, ['edit_button', 'cancel_button'])) {
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
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function submitForm(array &$element, FormStateInterface $form_state) {
    $pane_storage = self::getPaneStorage($element, $form_state);
    if ($pane_storage['reuse_profile']) {
      // Load the current profile by ID or by callback
      $profile = (is_numeric($element['#reuse_profile_source']))
        ? \Drupal::entityTypeManager()->getStorage('profile')->load($element['#reuse_profile_source'])
        : call_user_func($element['#reuse_profile_source'], $element, $form_state, $form_state->getCompleteForm());
      // There's no valid way to reuse a profile if one is not found.
      if (!$profile instanceof ProfileInterface) {
        throw new EntityStorageException('The profile to reuse could not be determined from the provided arguments.');
      }
      $element['#profile'] = $profile;
    }
    elseif (isset($pane_storage['mode']) && in_array($pane_storage['mode'], ['new', 'edit'])) {
      $form_display = EntityFormDisplay::collectRenderDisplay($element['#profile'], 'default');
      $form_display->extractFormValues($element['#profile'], $element, $form_state);
      $element['#profile']->save();
    }
  }

  /**
   * Profile AJAX callback.
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
  public static function profileReuseValidate(array $element, FormStateInterface $form_state) {
    $form = $form_state->getCompleteForm();
    $profile_element_parents = $element['#parents'];
    array_pop($profile_element_parents);
    $profile_element = NestedArray::getValue($form, $profile_element_parents);
    self::setPaneStorage($profile_element, $form_state, 'reuse_profile', $element['#value']);
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
      $pane_storage = self::getPaneStorage($profile_element, $form_state);
      $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
      $create_profile = ($element['#value'] == 'new_profile');
      if ($create_profile) {
        /** @var ProfileInterface $existing_profile */
        $existing_profile = $profile_element['#profile'];
        $profile = $profile_storage->create([
          'type' => $existing_profile->bundle(),
          'uid' => $existing_profile->getOwnerId(),
        ]);
      } else {
        $profile_id = $form_state->getValue($element['#parents']);
        $profile = $profile_storage->load($profile_id);
      }
      $pane_storage['profile'] = $profile;
      $pane_storage['mode'] = $create_profile ? 'new' : 'view';
      self::setPaneStorage($profile_element, $form_state, $pane_storage);
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
  public static function profileEditCancelValidate(array $element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element && $triggering_element['#id'] === $element['#id']) {
      $array_parents = $triggering_element['#array_parents'];
      $last_parent = array_pop($array_parents);
      if (in_array($last_parent, ['edit_button', 'cancel_button'])) {
        $complete_form = $form_state->getCompleteForm();
        $parents = $element['#parents'];
        array_pop($parents);
        $profile_element = NestedArray::getValue($complete_form, $parents);
        $mode = ($last_parent == 'edit_button') ? 'edit' : 'view';
        self::setPaneStorage($profile_element, $form_state, 'mode', $mode);
      }
    }
  }

  /**
   * Get the current pane storage values from the form state, or the defaults.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The pane storage array.
   */
  protected static function getPaneStorage(array &$element, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $pane_storage = isset($storage['pane_' . $element['#name']])
      ? $storage['pane_' . $element['#name']]
      : [];
    $defaults = [
      'profile' => NULL,
      'reuse_profile' => NULL,
      'mode' => 'view'
    ];
    return $pane_storage + $defaults;
  }

  /**
   * Stores one or all values to the pane storage.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param $keyOrValues
   *   Either the key to set, or an array to replace the entire pane storage
   *   with.
   * @param null $value
   *   The new value to set if setting a single key. Used only if $keyOrValue
   *   is not an array.
   */
  protected static function setPaneStorage(array &$element, FormStateInterface $form_state, $keyOrValues, $value = NULL) {
    if (is_array($keyOrValues)) {
      $pane_storage = $keyOrValues;
    } else {
      $pane_storage = self::getPaneStorage($element, $form_state);
      $pane_storage[$keyOrValues] = $value;
    }
    $storage = $form_state->getStorage();
    $storage['pane_' . $element['#name']] = $pane_storage;
    $form_state->setStorage($storage);
  }

  /**
   * Hides fields from the element which should not be visible.
   *
   * @param array $element
   *   The element.
   * @param array $retain
   *   An array of child element keys to keep visible.
   * @param bool $force_retained
   *   Whether or not to force retained fields to stay visible.
   */
  protected static function hideProfileFields(array &$element, $retain = [], $force_retained = FALSE) {
    foreach (Element::children($element) as $key) {
      if (!in_array($key, $retain)) {
        $element[$key]['#access'] = FALSE;
      } elseif ($force_retained) {
        $element[$key]['#access'] = TRUE;
      }
    }
  }

  /**
   * Get the current mode of the profile select element.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param ProfileInterface[] $profiles
   *   The user's profiles
   *
   * @return string
   *   The current mode.
   */
  protected static function getMode(array $element, FormStateInterface $form_state, $profiles = []) {
    $pane_storage = self::getPaneStorage($element, $form_state);
    $selected_profile = self::getProfileSelectValue($element, $form_state);

    if (is_null($selected_profile) && !empty($element['profile_selection']['#value'])) {
      $selected_profile = $element['profile_selection']['#value'];
    }

    // User is adding a new profile.
    if (!empty($selected_profile)) {
      $mode = ($selected_profile == 'new_profile') ? 'new' : 'view';
    }
    // If an AJAX rebuild happened, we might have our data in form state.
    elseif (!empty($pane_storage['profile']) && !empty($pane_storage['mode'])) {
      $mode = $pane_storage['mode'];
    }
    // If a new form, either view an existing profile or create a new one.
    else {
      $mode = (!empty($profiles)) ? 'view' : 'new';
    }

    // If no account profiles returned but we're viewing one, edit it instead.
    if ($mode == 'view' && empty($profiles)) {
      $mode = 'edit';
    }

    return $mode;
  }

  /**
   * Loads the current user's profiles.
   *
   * @param ProfileInterface $default_profile
   *   The default profile for the element.
   *
   * @return ProfileInterface[]
   *   The profiles.
   */
  protected static function getExistingProfiles(ProfileInterface $default_profile) {
    $profiles = [];
    if ($default_profile->getOwnerId() > 0) {
      $profile_ids = \Drupal::service('entity.query')
        ->get('profile')
        ->condition('uid', $default_profile->getOwnerId())
        ->condition('type', $default_profile->bundle())
        ->condition('status', TRUE)
        ->sort('profile_id', 'DESC')
        ->execute();
      $profiles = \Drupal::entityTypeManager()->getStorage('profile')->loadMultiple($profile_ids);
    }
    return $profiles;
  }

  /**
   * Returns the selected value from the form state, or null if no selection
   * has been submitted yet.
   *
   * @param array $element
   *   The profile select element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return int|string|NULL
   *   The selected profile ID, new_profile, or NULL.
   */
  protected static function getProfileSelectValue(array $element, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $pane_storage = self::getPaneStorage($element, $form_state);
    $parents = $element['#parents'];
    $parents[] = 'profile_selection';

    $selected_value = NULL;
    if (NestedArray::keyExists($values, $parents)) {
      $selected_value = NestedArray::getValue($values, $parents);
    }
    elseif (!empty($pane_storage['profile_selection'])) {
      $selected_value = $pane_storage['profile_selection'];
    }

    return $selected_value;
  }

  protected static function getDefaultProfile(array $element, FormStateInterface $form_state, $mode, array $profiles) {
    $selected_value = self::getProfileSelectValue($element, $form_state);
    $pane_storage = self::getPaneStorage($element, $form_state);
    $default_profile = $element['#default_value'];

    // If user wants to create a new profile, do so.
    if ($selected_value && $mode == 'new') {
      $default_profile = \Drupal::entityTypeManager()->getStorage('profile')->create([
        'type' => $default_profile->bundle(),
        'uid' => $default_profile->getOwnerId(),
      ]);
    }
    // Load profile from form state if it exists.
    elseif (!empty($pane_storage['profile'])) {
      $default_profile = $pane_storage['profile'];
    }
    // Select existing user profile if this is a new form.
    elseif(!$selected_value && $mode == 'view' && !$default_profile->id()) {
      $default_profile = reset($profiles);
    }

    return $default_profile;
  }

}
