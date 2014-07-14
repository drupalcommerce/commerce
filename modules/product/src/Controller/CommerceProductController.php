<?php

/**
 * @file
 * Contains \Drupal\commerce\Controller\CommerceProductController.
 */

namespace Drupal\commerce_product\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\Date;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\commerce_product\CommerceProductTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Commerce Product routes.
 */
class CommerceProductController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\Date
   */
  protected $date;

  /**
   * Constructs a CommerceProductController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Datetime\Date $date
   *   The date service.
   */
  public function __construct(Connection $database, Date $date) {
    $this->date = $date;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'), $container->get('date'));
  }


  /**
   * Displays add content links for available product types.
   *
   * Redirects to admin/commerce/products/add/{product-type} if only one product
   * type is available.
   *
   * @return array
   *   A render array for a list of the product types that can be added; however,
   *   if there is only one product type defined for the site, the function
   *   redirects to the product add page for that one product type and does not
   *   return at all.
   */
  public function addPage() {
    $content = array();

    // Only use product types the user has access to.
    foreach ($this->entityManager()->getStorage('commerce_product_type')->loadMultiple() as $commerce_product_type) {
      if ($this->entityManager()->getAccessController('commerce_product')->createAccess($commerce_product_type->id)) {
        $content[$commerce_product_type->id] = $commerce_product_type;
      }
    }

    // Bypass the admin/commerce/config/product/add listing if only one product type
    // is available.
    if (count($content) == 1) {
      $commerce_product_type = array_shift($content);
      return $this->redirect('commerce_product.add', array('commerce_product_type' => $commerce_product_type->id));
    }

    return array(
      '#theme' => 'commerce_product_add_list',
      '#content' => $content,
    );
  }

  /**
   * Provides the product add form.
   *
   * @param \Drupal\commerce_product\CommerceProductTypeInterface $commerce_product_type
   *   The product type entity for the product.
   *
   * @return array
   *   A product add form.
   */
  public function add(CommerceProductTypeInterface $commerce_product_type) {
    $langcode = $this->moduleHandler()->invoke('language', 'get_default_langcode', array('commerce_product', $commerce_product_type->id));

    $commerce_product = $this->entityManager()->getStorage('commerce_product')->create(array(
      'type' => $commerce_product_type->id,
      'langcode' => $langcode ? $langcode : $this->languageManager()->getCurrentLanguage()->id,
    ));

    $form = $this->entityFormBuilder()->getForm($commerce_product, 'add');

    return $form;
  }

  /**
   * The _title_callback for the commerce_product.add route.
   *
   * @param \Drupal\commerce_product\CommerceProductTypeInterface $commerce_product_type
   *   The current product.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(CommerceProductTypeInterface $commerce_product_type) {
    return $this->t('Create @label', array('@label' => $commerce_product_type->label()));
  }
}
