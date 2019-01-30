<?php

namespace Drupal\commerce_order\Element;

use Drupal\commerce\Element\CommerceElementTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Provides a form element for selecting a customer profile.
 *
 * @deprecated Use the customer_profile inline form instead.
 * @see https://www.drupal.org/node/3015309
 *
 * Usage example:
 * @code
 * $form['billing_profile'] = [
 *   '#type' => 'commerce_profile_select',
 *   '#default_value' => $profile,
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

    /** @var \Drupal\commerce\InlineFormManager $inline_form_manager */
    $inline_form_manager = \Drupal::service('plugin.manager.commerce_inline_form');
    $inline_form = $inline_form_manager->createInstance('customer_profile', [
      'available_countries' => $element['#available_countries'],
    ], $element['#default_value']);

    $element['#inline_form'] = $inline_form;
    $element = $inline_form->buildInlineForm($element, $form_state);
    // The updateProfile() callback needs to run after the inline form ones.
    $element['#element_validate'][] = [get_called_class(), 'updateProfile'];
    $element['#commerce_element_submit'][] = [get_called_class(), 'updateProfile'];

    return $element;
  }

  /**
   * Updates $element['#profile'] with the inline form's entity.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function updateProfile(array &$element, FormStateInterface $form_state) {
    /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
    $inline_form = $element['#inline_form'];
    $element['#profile'] = $inline_form->getEntity();
  }

}
