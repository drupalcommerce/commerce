<?php

/**
 * @file
 * Contains \Drupal\commerce\Controller\CommerceStoreController.
 */

namespace Drupal\commerce\Controller;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\commerce\CommerceStoreTypeInterface;
use Drupal\commerce\CommerceStoreInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Commerce Store routes.
 */
class CommerceStoreController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $date_formatter;

  /**
   * Constructs a CommerceStoreController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date service.
   */
  public function __construct(Connection $database, DateFormatter $date_formatter) {
    $this->database = $database;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * Displays add content links for available store types.
   *
   * Redirects to admin/commerce/config/store/add/{store-type} if only one store
   * type is available.
   *
   * @return array
   *   A render array for a list of the store types that can be added; however,
   *   if there is only one store type defined for the site, the function
   *   redirects to the store add page for that one store type and does not
   *   return at all.
   */
  public function addPage() {
    $content = array();

    // Only use store types the user has access to.
    foreach ($this->entityManager()->getStorage('commerce_store_type')->loadMultiple() as $commerce_store_type) {
      if ($this->entityManager()->getAccessControlHandler('commerce_store')->createAccess($commerce_store_type->id)) {
        $content[$commerce_store_type->id] = $commerce_store_type;
      }
    }

    // Bypass the admin/commerce/config/store/add listing if only one store type
    // is available.
    if (count($content) == 1) {
      $commerce_store_type = array_shift($content);
      return $this->redirect('commerce.store_add', array('commerce_store_type' => $commerce_store_type->id));
    }

    return array(
      '#theme' => 'commerce_store_add_list',
      '#content' => $content,
    );
  }

  /**
   * Provides the store add form.
   *
   * @param \Drupal\commerce\CommerceStoreTypeInterface $commerce_store_type
   *   The store type entity for the store.
   *
   * @return array
   *   A store add form.
   */
  public function add(CommerceStoreTypeInterface $commerce_store_type) {
    $account = $this->currentUser();
    $langcode = $this->moduleHandler()->invoke('language', 'get_default_langcode', array('commerce_store', $commerce_store_type->id));

    $commerce_store = $this->entityManager()->getStorage('commerce_store')->create(array(
      'type' => $commerce_store_type->id,
      'langcode' => $langcode ? $langcode : $this->languageManager()->getCurrentLanguage()->id,
    ));

    $form = $this->entityFormBuilder()->getForm($commerce_store, 'add');

    return $form;
  }

  /**
   * The _title_callback for the commerce.store_add route.
   *
   * @param \Drupal\commerce\CommerceStoreTypeInterface $commerce_store_type
   *   The current store.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(CommerceStoreTypeInterface $commerce_store_type) {
    return $this->t('Create @label', array('@label' => $commerce_store_type->label()));
  }

}
