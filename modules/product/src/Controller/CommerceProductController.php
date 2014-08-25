<?php

/**
 * @file
 * Contains \Drupal\commerce\Controller\CommerceProductController.
 */

namespace Drupal\commerce_product\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\commerce_product\CommerceProductTypeInterface;
use Drupal\commerce_product\CommerceProductInterface;
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
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $date_formatter;

  /**
   * Constructs a CommerceProductController object.
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
    $product_types = $this->entityManager()->getStorage('commerce_product_type')->loadMultiple();
    // Filter out the product types the user doesn't have access to.
    foreach ($product_types as $product_type_id => $product_type) {
      if (!$this->entityManager()->getAccessControlHandler('commerce_product')->createAccess($product_type_id)) {
        unset($product_types[$product_type_id]);
      }
    }

    if (count($product_types) == 1) {
      $product_type = reset($product_types);
      return $this->redirect('entity.commerce_product.add_form', array('commerce_product_type' => $product_type->id()));
    }

    return array(
      '#theme' => 'commerce_product_add_list',
      '#content' => $product_types,
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
    $langcode = $this->moduleHandler()->invoke('language', 'get_default_langcode', array('commerce_product', $commerce_product_type->id()));

    $commerce_product = $this->entityManager()->getStorage('commerce_product')->create(array(
      'type' => $commerce_product_type->id(),
      'langcode' => $langcode ? $langcode : $this->languageManager()->getCurrentLanguage()->id,
    ));

    $form = $this->entityFormBuilder()->getForm($commerce_product, 'add');

    return $form;
  }

  /**
   * The _title_callback for the entity.commerce_product.add_form route.
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

  /**
   * The _title_callback for the entity.commerce_product.edit_form route
   *
   * @param \Drupal\commerce_product\CommerceProductInterface $commerce_product
   *   The current product.
   *
   * @return string
   *   The page title
   */
  public function editPageTitle(CommerceProductInterface $commerce_product) {
    return $this->t('Editing @label', array('@label' => $commerce_product->label()));
  }

  /**
   * The _title_callback for the entity.commerce_product.view route
   *
   * @param \Drupal\commerce_product\CommerceProductInterface $commerce_product
   *   The current product.
   *
   * @return string
   *   The page title
   */
  public function viewProductTitle(CommerceProductInterface $commerce_product) {
    return \Drupal\Component\Utility\Xss::filter($commerce_product->label());
  }

}
