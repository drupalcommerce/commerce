<?php

/**
 * @file
 * The controller for the product entity containing the CRUD operations.
 */

/**
 * The controller class for products contains methods for the product CRUD operations.
 *
 * Mainly relies on the EntityAPIController class provided by the Entity
 * module, just overrides specific features.
 */
class CommerceProductEntityController extends DrupalCommerceEntityController {

  /**
   * Create a default product.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   * @return
   *   A product object with all default fields initialized.
   */
  public function create(array $values = array()) {
    $values += array(
      'product_id' => '',
      'is_new' => TRUE,
      'sku' => '',
      'title' => '',
      'uid' => '',
      'status' => 1,
      'created' => '',
      'changed' => '',
    );

    return parent::create($values);
  }

  /**
   * Saves a product.
   *
   * @param $product
   *   The full product object to save.
   * @param $transaction
   *   An optional transaction object.
   *
   * @return
   *   SAVED_NEW or SAVED_UPDATED depending on the operation performed.
   */
  public function save($entity, DatabaseTransaction $transaction = NULL) {
    $transaction = isset($transaction) ? $transaction : db_transaction();

    // Hardcode the changed time.
    $entity->changed = REQUEST_TIME;

    if (empty($entity->{$this->idKey}) || !empty($entity->is_new)) {
      // Set the creation timestamp if not set, for new entities.
      if (empty($entity->created)) {
        $entity->created = REQUEST_TIME;
      }
    }

    // Remove price components from any price fields attached to the product.
    // Default price components should instead be rebuilt each load using
    // hook_field_attach_load().
    foreach (field_info_instances('commerce_product', $entity->type) as $field_name => $instance) {
      // Load the instance's field data.
      $field = field_info_field($instance['field_name']);

      // If the instance is a price field with data on this product...
      if ($field['type'] == 'commerce_price' && !empty($entity->{$field_name})) {
        // Remove the price components from every price value.
        foreach ($entity->{$field_name} as $langcode => &$items) {
          foreach ($items as $delta => &$item) {
            if (!empty($item['data'])) {
              $item['data']['components'] = array();
            }
          }
        }
      }
    }

    return parent::save($entity);
  }

  /**
   * Unserializes the data property of loaded products.
   */
  public function attachLoad(&$queried_products, $revision_id = FALSE) {
    foreach ($queried_products as $product_id => &$product) {
      $product->data = unserialize($product->data);
    }

    // Call the default attachLoad() method. This will add fields and call
    // hook_commerce_product_load().
    parent::attachLoad($queried_products, $revision_id);
  }

  /**
   * Deletes multiple products by ID.
   *
   * @param $product_ids
   *   An array of product IDs to delete.
   * @param $transaction
   *   An optional transaction object.
   *
   * @return
   *   TRUE on success, FALSE otherwise.
   */
  public function delete($product_ids, DatabaseTransaction $transaction = NULL) {
    if (!empty($product_ids)) {
      $products = $this->load($product_ids, array());

      // Ensure the products can actually be deleted.
      foreach ((array) $products as $product_id => $product) {
        if (!commerce_product_can_delete($product)) {
          unset($products[$product_id]);
        }
      }

      // If none of the specified products can be deleted, return FALSE.
      if (empty($products)) {
        return FALSE;
      }

      parent::delete(array_keys($products), $transaction);
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Builds a structured array representing the entity's content.
   *
   * The content built for the entity will vary depending on the $view_mode
   * parameter.
   *
   * @param $entity
   *   An entity object.
   * @param $view_mode
   *   View mode, e.g. 'full', 'teaser'...
   * @param $langcode
   *   (optional) A language code to use for rendering. Defaults to the global
   *   content language of the current request.
   * @return
   *   The renderable array.
   */
  public function buildContent($product, $view_mode = 'full', $langcode = NULL, $content = array()) {
    // Prepare a reusable array representing the CSS file to attach to the view.
    $attached = array(
      'css' => array(drupal_get_path('module', 'commerce_product') . '/theme/commerce_product.css'),
    );

    // Add the default fields inherent to the product entity.
    $content['sku'] = array(
      '#markup' => theme('commerce_product_sku', array('sku' => check_plain($product->sku), 'label' => t('SKU:'), 'product' => $product)),
      '#attached' => $attached,
    );

    // Only display the title and status on the full page view.
    if ($view_mode == 'full') {
      $content['title'] = array(
        '#markup' => theme('commerce_product_title', array('title' => check_plain($product->title), 'label' => t('Title:'), 'product' => $product)),
        '#attached' => $attached,
      );

      $content['status'] = array(
        '#markup' => theme('commerce_product_status', array('status' => $product->status ? t('Active') : t('Disabled'), 'label' => t('Status:'), 'product' => $product)),
        '#attached' => $attached,
      );
    }

    return parent::buildContent($product, $view_mode, $langcode, $content);
  }
}
