<?php

namespace Drupal\commerce_order\Plugin\Commerce\InlineForm;

use Drupal\commerce\CurrentCountryInterface;
use Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentCountryInterface $current_country, EntityTypeBundleInfo $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentCountry = $current_country;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce.current_country'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // Unique identifier for the current instance of the inline form.
      // Passed along to field widgets. Examples: 'billing', 'shipping'.
      'instance_id' => '',
      // If empty, all countries will be available.
      'available_countries' => [],

      'use_address_book' => TRUE,
      // The uid of the account whose address book will be used.
      'address_book_uid' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function requiredConfiguration() {
    return ['instance_id'];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateConfiguration() {
    parent::validateConfiguration();

    if (!is_array($this->configuration['available_countries'])) {
      throw new \RuntimeException('The available_countries configuration value must be an array.');
    }
    if (empty($this->configuration['use_address_book'])) {
      $this->configuration['address_book_uid'] = 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildInlineForm(array $inline_form, FormStateInterface $form_state) {
    $inline_form = parent::buildInlineForm($inline_form, $form_state);
    // Allows a widget to vary when used for billing versus shipping purposes.
    // Available in hook_field_widget_form_alter() via $context['form'].
    $inline_form['#instance_id'] = $this->configuration['instance_id'];

    assert($this->entity instanceof ProfileInterface);
    if ($this->entity->isNew()) {
      if ($this->configuration['use_address_book'] && $this->configuration['address_book_uid']) {
        $default_profile = $this->loadDefaultProfile($this->configuration['address_book_uid']);
        if (!empty($default_profile)) {
          $this->entity->populateFromProfile($default_profile);
        }
      }
    }

    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, 'default');
    $form_display->buildForm($this->entity, $inline_form, $form_state);
    $inline_form = $this->prepareProfileForm($inline_form, $form_state);

    if ($this->configuration['use_address_book']) {
      $inline_form['copy_to_address_book'] = [
        '#type' => 'checkbox',
        '#title' => $this->getCopyLabel(),
        '#default_value' => (bool) $this->entity->getData('copy_to_address_book', TRUE),
        // The checkbox is not shown to anonymous customers, to avoid confusion.
        // The flag itself defaults to TRUE, cause the address should still
        // be copied if the customer registers or logs in.
        '#access' => !empty($this->configuration['address_book_uid']),
      ];
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

    if ($this->configuration['use_address_book']) {
      $values = $form_state->getValue($inline_form['#parents']);
      if (!empty($values['copy_to_address_book'])) {
        $this->entity->setData('copy_to_address_book', TRUE);
      }
    }
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

  /**
   * Loads the default profile for the given user ID.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   The default profile, or NULL if none found.
   */
  protected function loadDefaultProfile($uid) {
    $user_storage = $this->entityTypeManager->getStorage('user');
    /** @var \Drupal\user\UserInterface $user */
    $user = $user_storage->load($uid);
    if (!$user) {
      return NULL;
    }
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->entityTypeManager->getStorage('profile');
    $default_profile = $profile_storage->loadDefaultByUser($user, $this->entity->bundle());

    return $default_profile;
  }

  /**
   * Gets the copy label.
   *
   * @return string
   *   The copy label.
   */
  protected function getCopyLabel() {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo('profile');
    if (!empty($bundles[$this->entity->bundle()]['multiple'])) {
      $copy_label = $this->t('Save to my address book');
    }
    else {
      $copy_label = $this->t('Update my stored address');
    }

    return $copy_label;
  }

}
