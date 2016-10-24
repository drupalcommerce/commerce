<?php

namespace Drupal\commerce_order\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the order type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_order_type",
 *   label = @Translation("Order type"),
 *   label_singular = @Translation("order type"),
 *   label_plural = @Translation("order types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count order type",
 *     plural = "@count order types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_order\Form\OrderTypeForm",
 *       "edit" = "Drupal\commerce_order\Form\OrderTypeForm",
 *       "delete" = "Drupal\commerce_order\Form\OrderTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\commerce_order\OrderTypeListBuilder",
 *   },
 *   admin_permission = "administer commerce_order_type",
 *   config_prefix = "commerce_order_type",
 *   bundle_of = "commerce_order",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "workflow",
 *     "refresh_mode",
 *     "refresh_frequency"
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/order-types/add",
 *     "edit-form" = "/admin/commerce/config/order-types/{commerce_order_type}/edit",
 *     "delete-form" = "/admin/commerce/config/order-types/{commerce_order_type}/delete",
 *     "collection" = "/admin/commerce/config/order-types"
 *   }
 * )
 */
class OrderType extends ConfigEntityBundleBase implements OrderTypeInterface {

  /**
   * The order type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The order type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The order type workflow ID.
   *
   * @var string
   */
  protected $workflow;

  /**
   * The order type refresh mode.
   *
   * @var string
   */
  protected $refresh_mode;

  /**
   * The order type refresh frequency.
   *
   * @var int
   */
  protected $refresh_frequency;

  /**
   * {@inheritdoc}
   */
  public function getWorkflowId() {
    return $this->workflow;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkflowId($workflow_id) {
    $this->workflow = $workflow_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRefreshMode() {
    return $this->refresh_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function setRefreshMode($refresh_mode) {
    $this->refresh_mode = $refresh_mode;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRefreshFrequency() {
    // The refresh frequency must always be at least 1s.
    return !empty($this->refresh_frequency) ? $this->refresh_frequency : 1;
  }

  /**
   * {@inheritdoc}
   */
  public function setRefreshFrequency($refresh_frequency) {
    $this->refresh_frequency = $refresh_frequency;
    return $this;
  }

}
