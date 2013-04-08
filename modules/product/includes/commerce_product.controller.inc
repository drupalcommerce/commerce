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
      'product_id' => NULL,
      'is_new' => TRUE,
      'sku' => '',
      'revision_id' => NULL,
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
  public function save($product, DatabaseTransaction $transaction = NULL) {
    global $user;

    // Hardcode the changed time.
    $product->changed = REQUEST_TIME;

    if (empty($product->{$this->idKey}) || !empty($product->is_new)) {
      // Set the creation timestamp if not set, for new entities.
      if (empty($product->created)) {
        $product->created = REQUEST_TIME;
      }
    }
    else {
      // Otherwise if the product is not new but comes from an entity_create()
      // or similar function call that initializes the created timestamp and uid
      // value to empty strings, unset them to prevent destroying existing data
      // in those properties on update.
      if ($product->created === '') {
        unset($product->created);
      }
      if ($product->uid === '') {
        unset($product->uid);
      }
    }

    $product->revision_timestamp = REQUEST_TIME;
    $product->revision_uid = $user->uid;

    // Determine if we will be inserting a new product.
    $product->is_new = empty($product->product_id);

    if ($product->is_new || !empty($product->revision)) {
      // When inserting either a new product or revision, $entity->log must be set
      // because {commerce_product_revision}.log is a text column and therefore
      // cannot have a default value. However, it might not be set at this
      // point, so we ensure that it is at least an empty string in that case.
      if (!isset($product->log)) {
        $product->log = '';
      }
    }
    elseif (empty($product->log)) {
      // If we are updating an existing product without adding a new revision,
      // we need to make sure $entity->log is unset whenever it is empty. As
      // long as $entity->log is unset, drupal_write_record() will not attempt
      // to update the existing database column when re-saving the revision.
      unset($product->log);
    }

    // Remove price components from any price fields attached to the product.
    // Default price components should instead be rebuilt each load using
    // hook_field_attach_load().
    foreach (field_info_instances('commerce_product', $product->type) as $field_name => $instance) {
      // Load the instance's field data.
      $field = field_info_field($instance['field_name']);

      // If the instance is a price field with data on this product...
      if ($field['type'] == 'commerce_price' && !empty($product->{$field_name})) {
        // Remove the price components from every price value.
        foreach ($product->{$field_name} as $langcode => &$items) {
          foreach ($items as $delta => &$item) {
            if (!empty($item['data'])) {
              $item['data']['components'] = array();
            }
          }
        }
      }
    }

    return parent::save($product, $transaction);
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
      'css' => array(drupal_get_path('module', 'commerce_product') . '/theme/commerce_product.theme.css'),
    );

    // Add the default fields inherent to the product entity.
    $content['sku'] = array(
      '#markup' => theme('commerce_product_sku', array('sku' => $product->sku, 'label' => t('SKU:'), 'product' => $product)),
      '#attached' => $attached,
    );
    $content['title'] = array(
      '#markup' => theme('commerce_product_title', array('title' => $product->title, 'label' => t('Title:'), 'product' => $product)),
      '#attached' => $attached,
    );
    $content['status'] = array(
      '#markup' => theme('commerce_product_status', array('status' => $product->status, 'label' => t('Status:'), 'product' => $product)),
      '#attached' => $attached,
    );

    return parent::buildContent($product, $view_mode, $langcode, $content);
  }
}
