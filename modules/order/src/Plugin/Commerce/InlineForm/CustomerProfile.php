<?php

namespace Drupal\commerce_order\Plugin\Commerce\InlineForm;

use Drupal\commerce\CurrentCountryInterface;
use Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\profile\Entity\ProfileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The current country.
   *
   * @var \Drupal\commerce\CurrentCountryInterface
   */
  protected $currentCountry;

  /**
   * Constructs a new CustomerProfile object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce\CurrentCountryInterface $current_country
   *   The current country.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentCountryInterface $current_country) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentCountry = $current_country;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce.current_country')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // Where the profile is being used. Passed along to field widgets.
      'parent_entity_type' => 'commerce_order',
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
    $inline_form = $this->prepareProfileForm($inline_form, $form_state);

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

  /**
   * Prepares the profile form.
   *
   * @param array $profile_form
   *   The profile form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The prepared profile form.
   */
  protected function prepareProfileForm(array $profile_form, FormStateInterface $form_state) {
    if (!empty($profile_form['address']['widget'][0])) {
      $address_widget = &$profile_form['address']['widget'][0];
      // Remove the details wrapper from the address widget.
      $address_widget['#type'] = 'container';
      // Limit the available countries.
      $available_countries = $this->configuration['available_countries'];
      if ($available_countries) {
        $address_widget['address']['#available_countries'] = $available_countries;
      }
      // Provide a default country.
      $default_country = $this->currentCountry->getCountry();
      if ($default_country && empty($address_widget['address']['#default_value']['country_code'])) {
        $default_country = $default_country->getCountryCode();
        // The address element ensures that the default country is always
        // available, which must be avoided in this case, to prevent the
        // customer from ordering to an unsupported country.
        if (!$available_countries || in_array($default_country, $available_countries)) {
          $address_widget['address']['#default_value']['country_code'] = $default_country;
        }
      }
    }
    return $profile_form;
  }

}
