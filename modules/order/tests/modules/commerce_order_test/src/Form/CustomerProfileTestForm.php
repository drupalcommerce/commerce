<?php

namespace Drupal\commerce_order_test\Form;

use Drupal\commerce\InlineFormManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A form for testing the customer_profile inline form.
 */
class CustomerProfileTestForm extends FormBase {

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
   * The inline form manager.
   *
   * @var \Drupal\commerce\InlineFormManager
   */
  protected $inlineFormManager;

  /**
   * Constructs a new CustomerProfileTestForm object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce\InlineFormManager $inline_form_manager
   *   The inline form manager.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, InlineFormManager $inline_form_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->inlineFormManager = $inline_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_inline_form')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_customer_profile_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $profile = NULL, $admin = NULL) {
    if (!$profile) {
      $profile_storage = $this->entityTypeManager->getStorage('profile');
      /** @var \Drupal\profile\Entity\ProfileInterface $profile */
      $profile = $profile_storage->create([
        'type' => 'customer',
        'uid' => 0,
      ]);
    }

    $inline_form = $this->inlineFormManager->createInstance('customer_profile', [
      'profile_scope' => 'billing',
      'available_countries' => ['FR', 'RS', 'US'],
      'address_book_uid' => $this->currentUser->id(),
      // Turn on copy_on_save for admins to exercise that code path as well.
      'copy_on_save' => $admin,
      'admin' => $admin,
    ], $profile);

    $form['profile'] = [
      '#parents' => ['profile'],
      '#inline_form' => $inline_form,
    ];
    $form['profile'] = $inline_form->buildInlineForm($form['profile'], $form_state);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
    $inline_form = $form['profile']['#inline_form'];
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $inline_form->getEntity();
    /** @var \Drupal\address\AddressInterface $address */
    $address = $profile->get('address')->first();

    $this->messenger()->addMessage(t('The street is "@street" and the country code is @country_code. Address book: @address_book.', [
      '@street' => $address->getAddressLine1(),
      '@country_code' => $address->getCountryCode(),
      '@address_book' => $profile->getData('copy_to_address_book') ? 'Yes' : 'No',
    ]));
  }

}
