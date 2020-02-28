<?php

namespace Drupal\commerce_order\Controller;

use Drupal\commerce_order\AddressBookInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\profile\Entity\ProfileTypeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides the address book UI.
 *
 * Profile module provides a user tab per profile type.
 * However, a Commerce site might have multiple customer profile types
 * (e.g. one for billing, one for shipping), and they should all be managed
 * under a single "Address book" tab.
 */
class AddressBookController implements ContainerInjectionInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The address book.
   *
   * @var \Drupal\commerce_order\AddressBookInterface
   */
  protected $addressBook;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AddressBookController object.
   *
   * @param \Drupal\commerce_order\AddressBookInterface $address_book
   *   The address book.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(AddressBookInterface $address_book, EntityFormBuilderInterface $entity_form_builder, EntityTypeManagerInterface $entity_type_manager) {
    $this->addressBook = $address_book;
    $this->entityFormBuilder = $entity_form_builder;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_order.address_book'),
      $container->get('entity.form_builder'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Builds an edit title for the given profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   *
   * @return string
   *   The edit title.
   */
  public function editTitle(ProfileInterface $profile) {
    return $this->t('Edit %label', ['%label' => $profile->label()]);
  }

  /**
   * Builds a delete title for the given profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   *
   * @return string
   *   The delete title.
   */
  public function deleteTitle(ProfileInterface $profile) {
    return $this->t('Delete %label', ['%label' => $profile->label()]);
  }

  /**
   * Sets the given profile as default.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the overview page.
   */
  public function setDefault(ProfileInterface $profile) {
    $profile->setDefault(TRUE);
    $profile->save();
    $this->messenger()->addMessage($this->t('%label is now the default address.', ['%label' => $profile->label()]));
    $overview_url = Url::fromRoute('commerce_order.address_book.overview', [
      'user' => $profile->getOwnerId(),
      'profile' => $profile->id(),
    ]);

    return new RedirectResponse($overview_url->toString());
  }

  /**
   * Builds the overview page.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   *
   * @return array
   *   The response.
   */
  public function overviewPage(UserInterface $user) {
    $profile_types = $this->addressBook->loadTypes();
    $profile_types = $this->filterTypesByViewAccess($profile_types, $user);
    $profile_entity_type = $this->entityTypeManager->getDefinition('profile');
    $view_builder = $this->entityTypeManager->getViewBuilder('profile');
    $wrapper_element_type = count($profile_types) > 1 ? 'details' : 'container';
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheContexts($profile_entity_type->getListCacheContexts());
    $cacheability->addCacheTags($profile_entity_type->getListCacheTags());

    $build = [];
    $build['#attached']['library'][] = 'commerce_order/address_book';
    foreach ($profile_types as $profile_type_id => $profile_type) {
      $add_form_url = Url::fromRoute('commerce_order.address_book.add_form', [
        'user' => $user->id(),
        'profile_type' => $profile_type->id(),
      ]);
      if ($profile_type->allowsMultiple()) {
        $profiles = $this->addressBook->loadAll($user, $profile_type_id);
      }
      else {
        $profile = $this->addressBook->load($user, $profile_type_id);
        $profiles = [];
        if ($profile) {
          $profiles[$profile->id()] = $profile;
        }
      }

      $build[$profile_type_id] = [
        '#type' => $wrapper_element_type,
        '#title' => $profile_type->getDisplayLabel() ?: $profile_type->label(),
        '#open' => TRUE,
        '#attributes' => [
          'class' => [
            'address-book__container',
            'address-book__container--' . $profile_type_id,
          ],
        ],
      ];
      $build[$profile_type_id]['add'] = [
        '#type' => 'link',
        '#title' => $this->t('Add address'),
        '#url' => $add_form_url,
        '#attributes' => [
          'class' => ['address-book__add-link'],
        ],
        '#access' => $add_form_url->access(),
      ];
      $build[$profile_type_id]['profiles'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['address-book__profiles'],
        ],
        '#access' => !empty($profiles),
      ];
      foreach ($profiles as $profile_id => $profile) {
        $build[$profile_type_id]['profiles'][$profile_id] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['address-book__profile'],
          ],
          'profile' => $view_builder->view($profile),
          'operations' => $this->buildOperations($profile, $profile_type),
        ];
        // Allow default profiles to be styled differently.
        if ($profile->isDefault()) {
          $build[$profile_type_id]['profiles'][$profile_id]['#attributes']['class'][] = 'address-book__profile--default';
        }
      }
      if (empty($profiles)) {
        $build[$profile_type_id]['empty_text'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['address-book__empty-text'],
          ],
          '#plain_text' => $this->t('There are no addresses yet.'),
          // If the wrapper is a fieldset and there's an add link, the
          // empty text is redundant.
          '#access' => $wrapper_element_type == 'container' || !$add_form_url->access(),
        ];
      }
      $cacheability->addCacheableDependency($profile_type);
    }
    $cacheability->applyTo($build);

    return $build;
  }

  /**
   * Builds the add form.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   * @param \Drupal\profile\Entity\ProfileTypeInterface $profile_type
   *   The profile type.
   *
   * @return array
   *   The response.
   */
  public function addForm(UserInterface $user, ProfileTypeInterface $profile_type) {
    $profile = $this->entityTypeManager->getStorage('profile')->create([
      'uid' => $user->id(),
      'type' => $profile_type->id(),
    ]);
    return $this->entityFormBuilder->getForm($profile, 'address-book-add');
  }

  /**
   * Checks access for the overview page.
   *
   * Grants access if the current user is allowed to view at least one
   * customer profile type.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkOverviewAccess(UserInterface $user, AccountInterface $account) {
    $user_access = $user->access('view', $account, TRUE);
    if (!$user_access->isAllowed()) {
      // The account does not have access to the user's canonical page
      // ("/user/{user}"), don't allow access to any sub-pages either.
      return $user_access;
    }
    $profile_types = $this->addressBook->loadTypes();
    $profile_types = $this->filterTypesByViewAccess($profile_types, $user, $account);

    return AccessResult::allowedIf(!empty($profile_types))->addCacheTags(['config:profile_type_list']);
  }

  /**
   * Checks access for the add form.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   * @param \Drupal\profile\Entity\ProfileTypeInterface $profile_type
   *   The profile type.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkCreateAccess(UserInterface $user, ProfileTypeInterface $profile_type, AccountInterface $account) {
    $user_access = $user->access('view', $account, TRUE);
    if (!$user_access->isAllowed()) {
      // The account does not have access to the user's canonical page
      // ("/user/{user}"), don't allow access to any sub-pages either.
      return $user_access;
    }
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('profile');

    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = $access_control_handler->createAccess($profile_type->id(), $account, [
      'profile_owner' => $user,
    ], TRUE);
    if ($result->isAllowed()) {
      // There is no create any/own permission, confirm that the account is
      // either an administrator, or they're creating a profile for themselves.
      $admin_permission = $this->entityTypeManager->getDefinition('profile')->getAdminPermission();
      $owner_result = AccessResult::allowedIfHasPermission($account, $admin_permission)
        ->orIf(AccessResult::allowedIf($account->id() == $user->id()))
        ->cachePerUser();
      $result = $result->andIf($owner_result);

      // Deny access when the profile type only allows a single profile
      // per user, and such a profile already exists.
      if (!$profile_type->allowsMultiple()) {
        $profile = $this->addressBook->load($user, $profile_type->id());
        // The result is marked as non-cacheable because profiles change
        // too often for the result to be cached based on their list tag.
        $other_result = AccessResult::allowedIf(empty($profile))->mergeCacheMaxAge(0);
        $result = $result->andIf($other_result);
      }
      $result->addCacheableDependency($profile_type);
    }

    return $result;
  }

  /**
   * Filters out profile types that the current user is not allowed to view.
   *
   * @param \Drupal\profile\Entity\ProfileTypeInterface[] $profile_types
   *   The profile types.
   * @param \Drupal\user\UserInterface $owner
   *   The profile owner.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\profile\Entity\ProfileTypeInterface[]
   *   The filtered profile types.
   */
  protected function filterTypesByViewAccess(array $profile_types, UserInterface $owner, AccountInterface $account = NULL) {
    $storage = $this->entityTypeManager->getStorage('profile');
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('profile');
    foreach ($profile_types as $profile_type_id => $profile_type) {
      $profile_stub = $storage->create([
        'type' => $profile_type_id,
        'uid' => $owner->id(),
      ]);
      if (!$access_control_handler->access($profile_stub, 'view', $account)) {
        unset($profile_types[$profile_type_id]);
      }
    }

    return $profile_types;
  }

  /**
   * Builds the operation links for the given profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   * @param \Drupal\profile\Entity\ProfileTypeInterface $profile_type
   *   The profile type.
   *
   * @return array
   *   A renderable array with the operation links.
   */
  protected function buildOperations(ProfileInterface $profile, ProfileTypeInterface $profile_type) {
    $operations = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['address-book__operations'],
      ],
      '#weight' => 999,
    ];
    $operations['edit'] = [
      '#type' => 'link',
      '#title' => $this->t('Edit'),
      '#url' => Url::fromRoute('commerce_order.address_book.edit_form', [
        'user' => $profile->getOwnerId(),
        'profile' => $profile->id(),
      ]),
      '#attributes' => [
        'class' => ['address-book__edit-link'],
      ],
      '#access' => $profile->access('update'),
    ];
    if ($profile_type->allowsMultiple()) {
      $operations['delete'] = [
        '#type' => 'link',
        '#title' => $this->t('Delete'),
        '#url' => Url::fromRoute('commerce_order.address_book.delete_form', [
          'user' => $profile->getOwnerId(),
          'profile' => $profile->id(),
        ]),
        '#attributes' => [
          'class' => ['address-book__delete-link'],
        ],
        '#access' => $profile->access('delete'),
      ];
      $operations['set_default'] = [
        '#type' => 'link',
        '#title' => $this->t('Set as default'),
        '#url' => Url::fromRoute('commerce_order.address_book.set_default', [
          'user' => $profile->getOwnerId(),
          'profile' => $profile->id(),
        ]),
        '#attributes' => [
          'class' => ['address-book__set-default-link'],
        ],
        '#access' => $profile->access('update') && !$profile->isDefault(),
      ];
    }

    return $operations;
  }

}
