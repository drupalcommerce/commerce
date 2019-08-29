<?php

namespace Drupal\commerce_order\Plugin\Commerce\InlineForm;

use Drupal\commerce\CurrentCountryInterface;
use Drupal\commerce\EntityHelper;
use Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormBase;
use Drupal\commerce_order\AddressBookInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an inline form for managing a customer profile.
 *
 * Allows copying values to and from the customer's address book.
 *
 * Supports two modes, based on the profile type setting:
 * - Single: The customer can have only a single profile of this type.
 * - Multiple: The customer can have multiple profiles of this type.
 *
 * @CommerceInlineForm(
 *   id = "customer_profile",
 *   label = @Translation("Customer profile"),
 * )
 */
class CustomerProfile extends EntityInlineFormBase {

  /**
   * The address book.
   *
   * @var \Drupal\commerce_order\AddressBookInterface
   */
  protected $addressBook;

  /**
   * The current country.
   *
   * @var \Drupal\commerce\CurrentCountryInterface
   */
  protected $currentCountry;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The customer profile.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $entity;

  /**
   * Constructs a new CustomerProfile object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_order\AddressBookInterface $address_book
   *   The address book.
   * @param \Drupal\commerce\CurrentCountryInterface $current_country
   *   The current country.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AddressBookInterface $address_book, CurrentCountryInterface $current_country, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->addressBook = $address_book;
    $this->currentCountry = $current_country;
    $this->currentUser = $current_user;
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
      $container->get('commerce_order.address_book'),
      $container->get('commerce.current_country'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // Unique. Passed along to field widgets. Examples: 'billing', 'shipping'.
      'profile_scope' => '',
      // If empty, all countries will be available.
      'available_countries' => [],
      // The uid of the customer whose address book will be used.
      'address_book_uid' => 0,
      // Whether profile should be copied to the address book after saving.
      // Pass FALSE if copying is done at a later point (e.g. order placement).
      'copy_on_save' => TRUE,
      // Whether the customer profile is being managed by an administrator.
      'admin' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function requiredConfiguration() {
    return ['profile_scope'];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateConfiguration() {
    parent::validateConfiguration();

    if (!is_array($this->configuration['available_countries'])) {
      throw new \RuntimeException('The available_countries configuration value must be an array.');
    }
    if (empty($this->configuration['address_book_uid'])) {
      // Defer copying if the customer is still unknown.
      $this->configuration['copy_on_save'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildInlineForm(array $inline_form, FormStateInterface $form_state) {
    $inline_form = parent::buildInlineForm($inline_form, $form_state);
    // Allows a widget to vary when used for billing versus shipping purposes.
    // Available in hook_field_widget_form_alter() via $context['form'].
    $inline_form['#profile_scope'] = $this->configuration['profile_scope'];

    assert($this->entity instanceof ProfileInterface);
    $profile_type_id = $this->entity->bundle();
    $allows_multiple = $this->addressBook->allowsMultiple($profile_type_id);
    $customer = $this->loadUser($this->configuration['address_book_uid']);
    $available_countries = $this->configuration['available_countries'];
    $address_book_profile = NULL;
    if ($customer->isAuthenticated() && $allows_multiple) {
      // Multiple address book profiles are allowed, prepare the dropdown.
      $address_book_profiles = $this->addressBook->loadAll($customer, $profile_type_id, $available_countries);
      if ($address_book_profiles) {
        $user_input = (array) NestedArray::getValue($form_state->getUserInput(), $inline_form['#parents']);
        if (!empty($user_input['select_address'])) {
          // An option was selected, pre-fill the profile form.
          $address_book_profile = $this->getProfileForOption($user_input['select_address']);
        }
        elseif ($this->entity->isNew()) {
          // The customer profile form is being rendered for the first time.
          // Use the default profile to pre-fill the profile form.
          $address_book_profile = $this->selectDefaultProfile($address_book_profiles);
        }
      }

      $profile_options = $this->buildOptions($address_book_profiles);
      if ($address_book_profile) {
        $selected_option = $this->selectOptionForProfile($address_book_profile);
      }
      else {
        $selected_option = $this->entity->isNew() ? '_new' : '_original';
        // Select the address book profile, if the _original option was removed.
        if ($selected_option == '_original' && !isset($profile_options['_original'])) {
          $selected_option = $this->entity->getData('address_book_profile_id');
        }
      }

      $inline_form['select_address'] = [
        '#type' => 'select',
        '#title' => $this->t('Select an address'),
        '#options' => $profile_options,
        '#default_value' => $selected_option,
        '#access' => !empty($address_book_profiles),
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefresh'],
          'wrapper' => $inline_form['#id'],
        ],
        '#attributes' => [
          'class' => ['available-profiles'],
        ],
        '#weight' => -999,
      ];
    }
    elseif ($customer->isAuthenticated() && $this->entity->isNew()) {
      // A single address book profile is allowed.
      // The customer profile form is being rendered for the first time.
      // Use the default profile to pre-fill the profile form.
      $address_book_profile = $this->addressBook->load($customer, $profile_type_id, $available_countries);
    }

    // Copy field values from the address book profile to the actual profile.
    if ($address_book_profile) {
      $this->entity->populateFromProfile($address_book_profile);
      $this->entity->unsetData('copy_to_address_book');
      $this->entity->unsetData('address_book_profile_id');
      if (!$address_book_profile->isNew()) {
        $this->entity->setData('address_book_profile_id', $address_book_profile->id());
      }
    }

    if ($this->shouldRender($inline_form, $form_state)) {
      $view_builder = $this->entityTypeManager->getViewBuilder('profile');
      $inline_form['rendered'] = $view_builder->view($this->entity);
      $inline_form['edit_button'] = [
        '#type' => 'button',
        '#name' => $inline_form['#profile_scope'] . '_edit',
        '#value' => $this->t('Edit'),
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefresh'],
          'wrapper' => $inline_form['#id'],
        ],
        '#limit_validation_errors' => [$inline_form['#parents']],
        '#attributes' => [
          'class' => ['address-book-edit-button'],
        ],
      ];
    }
    else {
      // The $address_book_profile_id will be missing if the source
      // address book profile was deleted, or has never existed.
      $address_book_profile_id = $this->entity->getData('address_book_profile_id');
      $profile_storage = $this->entityTypeManager->getStorage('profile');
      if ($address_book_profile_id && !$profile_storage->load($address_book_profile_id)) {
        $this->entity->unsetData('address_book_profile_id');
      }

      $form_display = $this->loadFormDisplay();
      $form_display->buildForm($this->entity, $inline_form, $form_state);
      $inline_form = $this->prepareProfileForm($inline_form, $form_state);

      $edit = $address_book_profile ? !$address_book_profile->isNew() : !$this->entity->isNew();
      $update_on_copy = (bool) $this->entity->getData('address_book_profile_id');
      if ($allows_multiple) {
        // The copy checkbox is:
        // - Shown and checked for customers adding a new address.
        // - Shown and unchecked for admins adding a new address.
        // - Hidden and checked for customers and admins editing an address
        //   book profile which is still in sync with the current profile.
        // - Hidden and unchecked if the address book profile is no longer in
        //   sync (determined and done via the logic in buildOptions().
        $default_value = TRUE;
        if ($this->configuration['admin'] && !$edit) {
          $default_value = FALSE;
        }
        if ($edit && !$update_on_copy) {
          // The profile was originally not copied to the address book,
          // preserve that decision.
          $default_value = FALSE;
        }
        $visible = !$default_value || !$update_on_copy;
      }
      else {
        // The copy checkbox is:
        // - Hidden and checked for customers, since the address book is always
        //   supposed to reflect customer's last entered address.
        // - Shown and unchecked for admins, who need to opt-in to copying.
        $default_value = !$this->configuration['admin'];
        $visible = $this->configuration['admin'];
      }

      $inline_form['copy_to_address_book'] = [
        '#type' => 'checkbox',
        '#title' => $this->getCopyLabel($profile_type_id, $update_on_copy),
        '#default_value' => (bool) $this->entity->getData('copy_to_address_book', $default_value),
        '#weight' => 999,
        // Anonymous customers don't have an address book until they register
        // or log in, so the checkbox is not shown to them, to avoid confusion.
        '#access' => $customer->isAuthenticated() && $visible,
      ];
    }

    return $inline_form;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $inline_form = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -1));
    return $inline_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateInlineForm(array &$inline_form, FormStateInterface $form_state) {
    parent::validateInlineForm($inline_form, $form_state);

    if (!isset($inline_form['rendered'])) {
      $form_display = $this->loadFormDisplay();
      $form_display->extractFormValues($this->entity, $inline_form, $form_state);
      $form_display->validateFormValues($this->entity, $inline_form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitInlineForm(array &$inline_form, FormStateInterface $form_state) {
    parent::submitInlineForm($inline_form, $form_state);

    if (!isset($inline_form['rendered'])) {
      $form_display = $this->loadFormDisplay();
      $form_display->extractFormValues($this->entity, $inline_form, $form_state);
      $values = $form_state->getValue($inline_form['#parents']);
      if (!empty($values['copy_to_address_book'])) {
        $this->entity->setData('copy_to_address_book', TRUE);
      }
      else {
        $this->entity->unsetData('copy_to_address_book');
      }
    }
    $this->entity->save();

    if ($this->configuration['copy_on_save'] && $this->addressBook->needsCopy($this->entity)) {
      $customer = $this->loadUser($this->configuration['address_book_uid']);
      $this->addressBook->copy($this->entity, $customer);
    }
  }

  /**
   * Loads a user entity for the given user ID.
   *
   * Falls back to the anonymous user if the user ID is empty or unknown.
   *
   * @param string $uid
   *   The user ID.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity.
   */
  protected function loadUser($uid) {
    $customer = User::getAnonymousUser();
    if (!empty($uid)) {
      $user_storage = $this->entityTypeManager->getStorage('user');
      /** @var \Drupal\user\UserInterface $user */
      $user = $user_storage->load($uid);
      if ($user) {
        $customer = $user;
      }
    }

    return $customer;
  }

  /**
   * Loads the form display used to build the profile form.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   *   The form display.
   */
  protected function loadFormDisplay() {
    $form_display = EntityFormDisplay::collectRenderDisplay($this->entity, 'default');
    // The log message field should never be shown to customers.
    $form_display->removeComponent('revision_log_message');

    return $form_display;
  }

  /**
   * Determines whether the current profile should be shown rendered.
   *
   * @param array $inline_form
   *   The inline form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the complete form.
   *
   * @return bool
   *   TRUE if the profile should be shown rendered, FALSE otherwise.
   */
  protected function shouldRender(array $inline_form, FormStateInterface $form_state) {
    // Determine the name of the triggering element, if the form was rebuilt
    // by an element from the current inline form.
    $triggering_element_name = '';
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element) {
      $parents = array_slice($triggering_element['#parents'], 0, count($inline_form['#parents']));
      if ($inline_form['#parents'] === $parents) {
        $triggering_element_name = end($triggering_element['#parents']);
      }
    }

    $render_parents = array_merge($inline_form['#parents'], ['render']);
    if ($triggering_element_name == 'select_address') {
      // Reset the render flag to re-evaluate the newly selected profile.
      $form_state->set($render_parents, NULL);
    }
    elseif ($triggering_element_name == 'edit_button') {
      // The edit button was clicked, turn off profile rendering.
      $form_state->set($render_parents, FALSE);
    }
    $render = $form_state->get($render_parents);
    if (!isset($render)) {
      $render = !$this->isProfileIncomplete($this->entity);
      $form_state->set($render_parents, $render);
    }

    return $render;
  }

  /**
   * Prepares the profile form.
   *
   * @param array $profile_form
   *   The profile form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the complete form.
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
   * Builds the list of options for the given address book profiles.
   *
   * Adds the special _original and _new options.
   *
   * @param \Drupal\profile\Entity\ProfileInterface[] $address_book_profiles
   *   The address book profiles.
   *
   * @return array
   *   The profile options.
   */
  protected function buildOptions(array $address_book_profiles) {
    $profile_options = EntityHelper::extractLabels($address_book_profiles);
    // The customer profile is not new, indicating that it is being edited.
    // Add an _original option to allow the customer to revert their changes
    // after selecting a different option.
    if (!$this->entity->isNew()) {
      $profile_options['_original'] = $this->entity->label();
      $address_book_profile_id = $this->entity->getData('address_book_profile_id', 0);
      if (isset($address_book_profiles[$address_book_profile_id])) {
        $source_address_book_profile = $address_book_profiles[$address_book_profile_id];
        if ($source_address_book_profile->equalToProfile($this->entity)) {
          // Avoid having two identical options in the list.
          // Keep the address book option because it is sorted.
          unset($profile_options['_original']);
        }
        else {
          // There are two identical options in the list, but their profiles
          // are not identical, most likely because the source address book
          // was updated after it was used to populate the customer profile.
          // Add a suffix to both to help the customer differentiate.
          if ($profile_options['_original'] == $source_address_book_profile->label()) {
            $profile_options['_original'] = $this->t('@profile (original version)', [
              '@profile' => $this->entity->label(),
            ]);
            $profile_options[$address_book_profile_id] = $this->t('@profile (updated version)', [
              '@profile' => $this->entity->label(),
            ]);
          }
          // Don't update the address book profile, since it is out of sync.
          $this->entity->setData('copy_to_address_book', FALSE);
        }
      }
    }
    $profile_options['_new'] = $this->t('+ Enter a new address');

    return $profile_options;
  }

  /**
   * Selects the option ID for the given address book profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $address_book_profile
   *   The address book profile.
   *
   * @return string
   *   The option ID. A profile ID, or '_new'.
   */
  protected function selectOptionForProfile(ProfileInterface $address_book_profile) {
    if ($address_book_profile->isNew()) {
      $option_id = '_new';
    }
    else {
      $option_id = $address_book_profile->id();
    }

    return $option_id;
  }

  /**
   * Gets the address book profile for the given option ID.
   *
   * @param string $option_id
   *   The option ID. A profile ID, or a special value ('_original', '_new').
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   The profile, or NULL if none found.
   */
  protected function getProfileForOption($option_id) {
    $profile_storage = $this->entityTypeManager->getStorage('profile');
    /** @var \Drupal\profile\Entity\ProfileInterface $address_book_profile */
    if ($option_id == '_new') {
      $address_book_profile = $profile_storage->create([
        'type' => $this->entity->bundle(),
        'uid' => 0,
      ]);
    }
    elseif ($option_id == '_original') {
      // The inline form is built with the original $this->>entity,
      // there is no need to update it in this case.
      $address_book_profile = NULL;
    }
    else {
      assert(is_numeric($option_id));
      $address_book_profile = $profile_storage->load($option_id);
    }

    return $address_book_profile;
  }

  /**
   * Selects a default profile from the given set of address book profiles.
   *
   * @param \Drupal\profile\Entity\ProfileInterface[] $address_book_profiles
   *   The address book profiles.
   *
   * @return \Drupal\profile\Entity\ProfileInterface|false
   *   The selected default profile, or FALSE if no profiles were given.
   */
  protected function selectDefaultProfile(array $address_book_profiles) {
    $default_profile = reset($address_book_profiles);
    foreach ($address_book_profiles as $profile) {
      if ($profile->isDefault()) {
        $default_profile = $profile;
        break;
      }
    }

    return $default_profile;
  }

  /**
   * Checks whether the given profile is incomplete.
   *
   * A profile is incomplete if it has an empty required field.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   *
   * @return bool
   *   TRUE if the given profile is incomplete, FALSE otherwise.
   */
  protected function isProfileIncomplete(ProfileInterface $profile) {
    $violations = $profile->validate();

    return count($violations) > 0;
  }

  /**
   * Gets the copy label for the given profile type.
   *
   * @param string $profile_type_id
   *   The profile type ID.
   * @param bool $update_on_copy
   *   Whether the copy will update an existing address book profile.
   *
   * @return string
   *   The copy label.
   */
  protected function getCopyLabel($profile_type_id, $update_on_copy) {
    $is_owner = FALSE;
    if (!$this->configuration['admin']) {
      $is_owner = $this->currentUser->id() == $this->configuration['address_book_uid'];
    }

    if ($this->addressBook->allowsMultiple($profile_type_id) && $is_owner) {
      if ($update_on_copy) {
        $copy_label = $this->t('Also update the address in my address book.');
      }
      else {
        $copy_label = $this->t('Save to my address book.');
      }
    }
    else {
      if ($update_on_copy) {
        $copy_label = $this->t("Also update the address in the customer's address book.");
      }
      else {
        $copy_label = $this->t("Save to the customer's address book.");
      }
    }

    return $copy_label;
  }

}
