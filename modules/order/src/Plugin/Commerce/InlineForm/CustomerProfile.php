<?php

namespace Drupal\commerce_order\Plugin\Commerce\InlineForm;

use Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Provides an inline form for managing a customer profile.
 *
 * @CommerceInlineForm(
 *   id = "customer_profile",
 *   label = @Translation("Customer profile"),
 * )
 */
class CustomerProfile extends EntityInlineFormBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // Where the profile is being used. Passed along to field widgets.
      'parent_entity_type' => 'commerce_order',
      // The country to select if the address widget doesn't have a default.
      'default_country' => NULL,
      // If empty, all countries will be available.
      'available_countries' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateConfiguration() {
    parent::validateConfiguration();

    if (!is_array($this->configuration['available_countries'])) {
      throw new \RuntimeException('The available_countries configuration value must be an array.');
    }
    // Make sure that the specified default country is available.
    if (!empty($this->configuration['default_country']) && !empty($this->configuration['available_countries'])) {
      if (!in_array($this->configuration['default_country'], $this->configuration['available_countries'])) {
        $this->configuration['default_country'] = NULL;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildInlineForm(array $inline_form, FormStateInterface $form_state) {
    $inline_form = parent::buildInlineForm($inline_form, $form_state);
    // Allows a widget to vary when used for billing versus shipping purposes.
    // Available in hook_field_widget_form_alter() via $context['form'].
    $inline_form['#parent_entity_type'] = $this->configuration['parent_entity_type'];

    assert($this->entity instanceof ProfileInterface);
    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, 'default');
    $form_display->buildForm($this->entity, $inline_form, $form_state);
    if (!empty($inline_form['address']['widget'][0])) {
      $widget_element = &$inline_form['address']['widget'][0];
      // Remove the details wrapper from the address widget.
      $widget_element['#type'] = 'container';
      // Provide a default country.
      $default_country = $this->configuration['default_country'];
      if ($default_country && empty($widget_element['address']['#default_value']['country_code'])) {
        $widget_element['address']['#default_value']['country_code'] = $default_country;
      }
      // Limit the available countries.
      $available_countries = $this->configuration['available_countries'];
      if ($available_countries) {
        $widget_element['address']['#available_countries'] = $available_countries;
      }
    }

    return $inline_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateInlineForm(array &$inline_form, FormStateInterface $form_state) {
    parent::validateInlineForm($inline_form, $form_state);

    assert($this->entity instanceof ProfileInterface);
    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, 'default');
    $form_display->extractFormValues($this->entity, $inline_form, $form_state);
    $form_display->validateFormValues($this->entity, $inline_form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitInlineForm(array &$inline_form, FormStateInterface $form_state) {
    parent::submitInlineForm($inline_form, $form_state);

    assert($this->entity instanceof ProfileInterface);
    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, 'default');
    $form_display->extractFormValues($this->entity, $inline_form, $form_state);
    $this->entity->save();
  }

}
