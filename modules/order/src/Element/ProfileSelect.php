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
      // The profile that to be submitted. Populated automatically.
      '#profile' => NULL,
      // The operation, 'view', 'add', or 'edit'. Usually populated automatically.
      '#op' => NULL,
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

    // Assign shared variables.
    $called_class = get_called_class();
    $storage = $form_state->getStorage();
    $pane_storage = isset($storage['pane_' . $element['#name']])
      ? $storage['pane_' . $element['#name']]
      : [];
    $profile_selection = isset($pane_storage['profile_selection'])
      ? $pane_storage['profile_selection']
      : NULL;
    $reuse_profile = isset($pane_storage['reuse_profile'])
      ? $pane_storage['reuse_profile']
      : $element['#reuse_profile_default'];

    // Define AJAX wrapper
    $ajax_wrapper_id = Html::getUniqueId('profile-select-ajax-wrapper');
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $element['#suffix'] = '</div>';

    // Load authenticated user's profiles.
    $profiles = [];
    if ($element['#default_value']->getOwnerId() > 0) {
      $profile_ids = \Drupal::service('entity.query')
        ->get('profile')
        ->condition('uid', $element['#default_value']->getOwnerId())
        ->condition('type', $element['#default_value']->bundle())
        ->condition('status', TRUE)
        ->sort('profile_id', 'DESC')
        ->execute();
      $profiles = \Drupal::entityTypeManager()->getStorage('profile')->loadMultiple($profile_ids);
    }

    // Determine operation to perform.
    if (isset($pane_storage['op'])) {
      $element['#op'] = $pane_storage['op'];
    }
    elseif ($profile_selection) {
      $element['#op'] = ($profile_selection == 'new_profile') ? 'add' : 'view';
    }
    elseif (is_null($element['#op'])) {
      $element['#op'] = (!empty($profiles)) ? 'view' : 'add';
    }
    // If no account profiles returned but we're viewing one, edit it instead.
    if ($element['#op'] === 'view' && empty($profiles)) {
      $element['#op'] = 'edit';
    }

    // Determine default profile
    $default_profile = $element['#default_value'];
    // If user wants to create a new profile, do so.
    if ($profile_selection) {
      $default_profile = ($profile_selection == 'new_profile')
        ? \Drupal::entityTypeManager()->getStorage('profile')->create([
          'type' => $default_profile->bundle(),
          'uid' => $default_profile->getOwnerId(),
        ])
        : $profiles[$profile_selection];
    }
    elseif($element['#op'] == 'view' && !$default_profile->id()) {
      $default_profile = reset($profiles);
    }
    $element['#default_value'] = $default_profile;
    $element['#profile'] = $default_profile;

    // Set default address field options.
    $form_display = EntityFormDisplay::collectRenderDisplay($default_profile, 'default');
    $form_display->buildForm($default_profile, $element, $form_state);
    if (!empty($element['address']['widget'][0])) {
      $widget_element = &$element['address']['widget'][0];
      // Remove the details wrapper from the address widget.
      $widget_element['#type'] = 'container';
      // Provide a default country.
      if (!empty($element['#default_country']) && $element['#op'] === 'add') {
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
      if ($element['#op'] != 'edit' && !empty($profiles)) {
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

      if ($element['#op'] == 'view') {
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
      elseif (!empty($profiles) && $element['#op'] == 'edit') {
        $element['cancel_button'] = [
          '#type' => 'button',
          '#name' => 'pane-' . $element['#name'] . '-cancel',
          '#value' => t('Cancel and select profile'),
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
    $last_parent = array_pop($triggering_parents);
    $storage = $form_state->getStorage();
    $pane_storage = isset($storage['pane_' . $element['#name']])
      ? $storage['pane_' . $element['#name']]
      : [];
    if ((!isset($pane_storage['reuse_profile']) || !$pane_storage['reuse_profile']) && !in_array($last_parent, ['edit_button', 'cancel_button'])) {
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
    $storage = $form_state->getStorage();
    if (isset($storage['pane_' . $element['#name']]['reuse_profile'])
      && $storage['pane_' . $element['#name']]['reuse_profile']) {
      // Load the current profile by ID or by callback.
      $profile = (is_numeric($element['#reuse_profile_source']))
        ? \Drupal::entityTypeManager()->getStorage('profile')->load($element['#reuse_profile_source'])
        : call_user_func($element['#reuse_profile_source'], $element, $form_state, $form_state->getCompleteForm());
      if (!$profile instanceof ProfileInterface) {
        throw new EntityStorageException('The profile to reuse could not be determined from the provided arguments.');
      }
      $element['#profile'] = $profile;
    }
    elseif (isset($storage['pane_' . $element['#name']]['op'])
      && in_array($storage['pane_' . $element['#name']]['op'], ['add', 'edit'])) {
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
    $profile_form = NestedArray::getValue($form, $array_parents);
    return $profile_form;
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
    $parents = $element['#array_parents'];
    array_pop($parents);
    $storage = $form_state->getStorage();
    $profile_form = NestedArray::getValue($form, $parents);
    $storage['pane_' . $profile_form['#name']]['reuse_profile'] = $element['#value'];
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
    $form = $form_state->getCompleteForm();
    $parents = $element['#array_parents'];
    array_pop($parents);
    $storage = $form_state->getStorage();
    $profile_form = NestedArray::getValue($form, $parents);
    $storage['pane_' . $profile_form['#name']]['profile_selection'] = $element['#value'];
    if (isset($storage['pane_' . $profile_form['#name']]['op']) && $storage['pane_' . $profile_form['#name']]['op'] != 'edit') {
      $storage['pane_' . $profile_form['#name']]['op'] = $element['#value'] == 'new_profile' ? 'add' : 'view';
    }
    $form_state->setStorage($storage);
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
      $parents = $triggering_element['#array_parents'];
      $last_parent = array_pop($parents);
      if (in_array($last_parent, ['edit_button', 'cancel_button'])) {
        $form = $form_state->getCompleteForm();
        $profile_form = NestedArray::getValue($form, $parents);
        $storage = $form_state->getStorage();
        $storage['pane_' . $profile_form['#name']]['op'] = ($last_parent == 'edit_button') ? 'edit' : 'view';
        $form_state->setStorage($storage);
      }
    }
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

}
