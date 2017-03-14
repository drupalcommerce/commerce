<?php

namespace Drupal\commerce_order\Element;

use Drupal\commerce\Element\CommerceElementBase;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
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

    $element['#profile'] = $element['#default_value'];

    $pane_id = $element['#name'];
    $mode = 'view';

    $profile_selection_parents = $element['#parents'];
    $profile_selection_parents[] = 'profile_selection';
    $profile_id = $form_state->getValue($profile_selection_parents);
    $storage = $form_state->getStorage();
    // User is adding a new profile.
    if ($profile_id && $profile_id == 'new_address') {
      $mode = 'new';
      unset($storage['pane_' . $pane_id]);
    }
    // If an AJAX rebuild happened, we might have our data in form state.
    elseif (!empty($storage['pane_' . $pane_id])) {
      $profile = $storage['pane_' . $pane_id]['profile'];
      $mode = $storage['pane_' . $pane_id]['mode'];
    }

    $ajax_wrapper_id = Html::getUniqueId('profile-select-ajax-wrapper');
    // Prefix and suffix used for Ajax replacement.
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $element['#suffix'] = '</div>';

    // Fetch all profiles of the user.
    $profile_uid = $element['#profile']->getOwnerId();
    $profile_ids = \Drupal::service('entity.query')
      ->get('profile')
      ->condition('uid', $profile_uid)
      ->condition('status', TRUE)
      ->sort('profile_id', 'DESC')
      ->execute();
    $profiles =  Profile::loadMultiple($profile_ids);
    $profile_options = [];
    /** @var Profile $profile_option */
    foreach ($profiles as $profile_option) {
      if (!isset($default_profile_id)) {
        $default_profile_id = $profile_option->id();
        $default_profile = $profile_option;
      }
      $profile_options[$profile_option->id()] = $profile_option->label();
    }
    $profile_options['new_address'] = t('+ Enter a new address');

    // No profile set yet. First see if one exists already.
    if (empty($profile)) {
      // No profiles found or user wants to create a new one. Do it.
      if (!$profiles || $mode == 'new') {
        $values = [
          'type' => $element['#profile']->bundle(),
          'uid' =>  $element['#profile']->getOwnerId(),
        ];
        $profile = Profile::create($values);
        $mode = 'new';
      }
      // Set the latest profile as the default one.
      else {
        $profile = $default_profile;
      }
    }

    // Remember the current profile and mode in form state.
    $storage['pane_' . $pane_id] = [
      'profile' => $profile,
      'mode' => $mode,
    ];
    $form_state->setStorage($storage);
    $element['#profile'] = $profile;

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
    if ($profile_options && $mode != 'edit') {
      \Drupal::moduleHandler()->alter('commerce_addressbook_labels', $profile_options, $profiles);

      $element['profile_selection'] = [
        '#title' => t('Select an address'),
        '#options' => $profile_options,
        '#type' => 'select',
        '#weight' => -5,
        '#default_value' => $default_profile_id,
        '#ajax' => [
          'callback' => [$called_class, 'profileSelectAjax'],
          'wrapper' => $ajax_wrapper_id,
        ],
        '#element_validate' => [[$called_class, 'profileSelectValidate']],
      ];
    }

    // Viewing a profile.
    if ($mode == 'view') {
      $view_builder = \Drupal::entityTypeManager()
        ->getViewBuilder('profile');
      $content = $view_builder->view($profile, 'default');

      $element['rendered_profile'] = [
        $content,
      ];
      $element['edit_button'] = [
        '#type' => 'button',
        '#name' => 'pane-' . $pane_id . '-edit',
        '#value' => t('Edit'),
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [$called_class, 'profileSelectAjax'],
          'wrapper' => $ajax_wrapper_id,
        ],
        '#element_validate' => [[$called_class, 'profileEditCancelValidate']],
      ];
      $element['address']['#access'] = FALSE;
    }
    // Add the field widgets for an existing profile.
    elseif ($profiles && $mode == 'edit') {
      $element['cancel_button'] = [
        '#type' => 'button',
        '#name' => 'pane-' . $pane_id . '-cancel',
        '#value' => t('Return to address selection'),
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [$called_class, 'profileSelectAjax'],
          'wrapper' => $ajax_wrapper_id,
        ],
        '#element_validate' => [[$called_class, 'profileEditCancelValidate']],
      ];
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
      $last_parent = array_pop($triggering_element['#parents']);
      if ($last_parent == 'edit_button') {
        $element['address']['#access'] = TRUE;
        $element['edit_button']['#access'] = FALSE;
        $element['rendered_profile']['#access'] = FALSE;
        $element['profile_selection']['#access'] = FALSE;
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
    $triggering_last_parent = array_pop($form_state->getTriggeringElement()['#parents']);
    if (!in_array($triggering_last_parent, ['edit_button', 'cancel_button'])) {
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
   * Form AJAX callback.
   *
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The Request object.
   *
   * @return array
   *   The form element replace the wrapper with.
   */
  public static function profileSelectAjax(&$form, FormStateInterface &$form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    array_pop($triggering_element['#array_parents']);
    $element = NestedArray::getValue($form, $triggering_element['#array_parents']);
    return $element;
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
    if (in_array('profile_selection', $triggering_element['#parents']) && $triggering_element['#id'] == $element['#id']) {
      $form = $form_state->getCompleteForm();
      $profile_element_parents = $element['#parents'];
      array_pop($profile_element_parents);
      $profile_element = NestedArray::getValue($form, $profile_element_parents);
      $pane_id = $profile_element['#name'];
      $storage = $form_state->getStorage();

      // If the user wants to enter a new address.
      if ($element['#value'] == 'new_address') {
        $storage['pane_' . $pane_id]['mode'] = 'new';
        $values = [
          'type' => $profile_element['#profile']->bundle(),
          'uid' =>  $profile_element['#profile']->getOwnerId(),
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
    if ($triggering_element) {
      $last_parent = array_pop($triggering_element['#parents']);
      if (in_array($last_parent, ['edit_button', 'cancel_button'])) {
        $complete_form = $form_state->getCompleteForm();
        array_pop($element['#parents']);
        $element = NestedArray::getValue($complete_form, $element['#parents']);
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
