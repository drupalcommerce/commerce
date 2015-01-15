<?php

/**
 * @file
 * Contains \Drupal\commerce_payment\CommercePaymentInfoType.
 */

namespace Drupal\commerce_payment\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\commerce_payment\CommercePaymentInfoTypeInterface;

/**
 * Defines the Payment information type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "commerce_payment_info_type",
 *   label = @Translation("Payment information type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_payment\Form\CommercePaymentInfoTypeForm",
 *       "edit" = "Drupal\commerce_payment\Form\CommercePaymentInfoTypeForm",
 *       "delete" = "Drupal\commerce_payment\Form\CommercePaymentInfoTypeDeleteForm"
 *     },
 *     "list_builder" = "Drupal\commerce_payment\CommercePaymentInfoTypeListBuilder",
 *   },
 *   admin_permission = "administer payment information types",
 *   config_prefix = "commerce_payment_info_type",
 *   bundle_of = "commerce_payment_info",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "/admin/commerce/config/payment-info-types/{commerce_payment_info_type}",
 *     "delete-form" = "/admin/commerce/payment-info/{commerce_payment_info}/delete"
 *   }
 * )
 */
class CommercePaymentInfoType extends ConfigEntityBundleBase implements CommercePaymentInfoTypeInterface {

  /**
   * The payment information type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The payment information type label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this payment information type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

}
