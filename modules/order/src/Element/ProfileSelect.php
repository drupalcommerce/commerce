<?php

namespace Drupal\commerce_order\Element;

use Drupal\commerce\Element\CommerceElementTrait;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\profile\Entity\ProfileInterface;

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
 *   '#profile_type' => 'customer',
 *   '#owner_uid' => \Drupal::currentUser()->id(),
 * ];
 * @endcode
 *
 * @FormElement("commerce_profile_select")
 */
class ProfileSelect extends FormElement {

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
      '#title' => t('Select a profile'),
      '#create_title' => t('+ Enter a new profile'),

      // The profile entity operated on. Required.
      '#default_value' => '_new',
      '#owner_uid' => 0,
      // Provide default to not break contrib which have outdated elements.
      '#profile_type' => 'customer',
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
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if (!empty($input['profile_selection'])) {
      $value = $input['profile_selection'];
    }
    elseif ($element['#default_value'] instanceof ProfileInterface) {
      $value = $element['#default_value']->id();
    }
    elseif (!empty($element['#default_value'])) {
      $value = $element['#default_value'];
    }
    else {
      $value = '_new';
    }

    return $value;
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
    if (!is_array($element['#available_countries'])) {
      throw new \InvalidArgumentException('The commerce_profile_select #available_countries property must be an array.');
    }

    if (empty($element['#profile_type'])) {
      throw new \InvalidArgumentException('The commerce_profile_select #profile_type property must be provided.');
    }
    $entity_type_manager = \Drupal::entityTypeManager();
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $entity_type_manager->getStorage('profile');
    /** @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
    $profile_type = $entity_type_manager->getStorage('profile_type')->load($element['#profile_type']);

    $user_profiles = [];
    /** @var \Drupal\user\UserInterface $user */
    $user = $entity_type_manager->getStorage('user')->load($element['#owner_uid']);

    if (!$user->isAnonymous()) {
      // If the user exists, attempt to load other profiles for selection.
      foreach ($profile_storage->loadMultipleByUser($user, $profile_type->id(), TRUE) as $existing_profile) {
        $user_profiles[$existing_profile->id()] = $existing_profile->label();

        // If this is the first form build, set the element's value based on
        // the user's default profile.
        if (!$form_state->isProcessingInput() && $existing_profile->isDefault()) {
          $element['#value'] = $existing_profile->id();
        }
      }
    }

    $id_prefix = implode('-', $element['#parents']);
    $wrapper_id = Html::getUniqueId($id_prefix . '-ajax-wrapper');
    $element = [
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      // Pass the id along to other methods.
      '#wrapper_id' => $wrapper_id,
      '#element_mode' => $form_state->get('element_mode') ?: 'view',
    ] + $element;

    if (!empty($user_profiles)) {
      $element['profile_selection'] = [
        '#title' => $element['#title'],
        '#options' => $user_profiles + ['_new' => $element['#create_title']],
        '#type' => 'select',
        '#weight' => -5,
        '#default_value' => $element['#value'],
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefresh'],
          'wrapper' => $wrapper_id,
        ],
        '#element_mode' => 'view',
      ];
    }
    else {
      $element['profile_selection'] = [
        '#type' => 'value',
        '#value' => '_new',
        '#element_mode' => 'create',
      ];
    }

    /** @var \Drupal\profile\Entity\ProfileInterface $element_profile */
    if ($element['#value'] == '_new') {
      $element_profile = $profile_storage->create([
        'type' => $profile_type->id(),
        'uid' => $user->id(),
      ]);
      $element['#element_mode'] = 'create';
    }
    else {
      $element_profile = $profile_storage->load($element['#value']);
    }

    // Viewing a profile.
    if (!$element_profile->isNew() && $element['#element_mode'] == 'view') {
      $view_builder = $entity_type_manager->getViewBuilder('profile');
      $element['rendered_profile'] = $view_builder->view($element_profile, 'default');

      $element['edit_button'] = [
        '#type' => 'submit',
        '#value' => t('Edit'),
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefresh'],
          'wrapper' => $wrapper_id,
        ],
        '#submit' => [[get_called_class(), 'ajaxSubmit']],
        '#name' => 'edit_profile',
        '#element_mode' => 'edit',
      ];
    }
    else {
      $form_display = EntityFormDisplay::collectRenderDisplay($element_profile, 'default');
      $form_display->buildForm($element_profile, $element, $form_state);

      // @todo Loop over all possible address fields.
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
    $value = $form_state->getValue($element['#parents']);

    $entity_type_manager = \Drupal::entityTypeManager();
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $entity_type_manager->getStorage('profile');
    /** @var \Drupal\profile\Entity\ProfileInterface $element_profile */
    if ($value['profile_selection'] == '_new') {
      $element_profile = $profile_storage->create([
        'type' => $element['#profile_type'],
        'uid' => $element['#owner_uid'],
      ]);
    }
    else {
      $element_profile = $profile_storage->load($value['profile_selection']);
    }

    if ($element['#element_mode'] != 'view' && $form_state->isSubmitted()) {
      $form_display = EntityFormDisplay::collectRenderDisplay($element_profile, 'default');
      $form_display->extractFormValues($element_profile, $element, $form_state);
      $form_display->validateFormValues($element_profile, $element, $form_state);
    }

    $form_state->setValueForElement($element, $element_profile);
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
    $element_profile = $form_state->getValue($element['#parents']);

    if ($element['#element_mode'] != 'view' && $form_state->isSubmitted()) {
      $form_display = EntityFormDisplay::collectRenderDisplay($element_profile, 'default');
      $form_display->extractFormValues($element_profile, $element, $form_state);
      $element_profile->save();
    }

    $form_state->setValueForElement($element, $element_profile);
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -1));
    return $element;
  }

  /**
   * Ajax submit callback.
   */
  public static function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $form_state->set('element_mode', $triggering_element['#element_mode']);
    $form_state->setRebuild();
  }

}
