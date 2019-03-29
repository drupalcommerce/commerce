<?php

namespace Drupal\commerce_promotion\Entity;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Coupon entity.
 *
 * @ContentEntityType(
 *   id = "commerce_promotion_coupon",
 *   label = @Translation("Coupon"),
 *   label_singular = @Translation("coupon"),
 *   label_plural = @Translation("coupons"),
 *   label_count = @PluralTranslation(
 *     singular = "@count coupon",
 *     plural = "@count coupons",
 *   ),
 *   handlers = {
 *     "event" = "Drupal\commerce_promotion\Event\CouponEvent",
 *     "list_builder" = "Drupal\commerce_promotion\CouponListBuilder",
 *     "storage" = "Drupal\commerce_promotion\CouponStorage",
 *     "access" = "Drupal\commerce_promotion\CouponAccessControlHandler",
 *     "views_data" = "Drupal\commerce\CommerceEntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\commerce_promotion\Form\CouponForm",
 *       "edit" = "Drupal\commerce_promotion\Form\CouponForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *    "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\commerce_promotion\CouponRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_promotion_coupon",
 *   admin_permission = "administer commerce_promotion",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "code",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   links = {
 *     "add-form" = "/promotion/{commerce_promotion}/coupons/add",
 *     "edit-form" = "/promotion/{commerce_promotion}/coupons/{commerce_promotion_coupon}/edit",
 *     "delete-form" = "/promotion/{commerce_promotion}/coupons/{commerce_promotion_coupon}/delete",
 *     "collection" = "/promotion/{commerce_promotion}/coupons",
 *   },
 * )
 */
class Coupon extends ContentEntityBase implements CouponInterface {

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['commerce_promotion'] = $this->getPromotionId();
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getPromotion() {
    return $this->get('promotion_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getPromotionId() {
    return $this->get('promotion_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getCode() {
    return $this->get('code')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCode($code) {
    $this->set('code', $code);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsageLimit() {
    return $this->get('usage_limit')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setUsageLimit($usage_limit) {
    $this->set('usage_limit', $usage_limit);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setEnabled($enabled) {
    $this->set('status', (bool) $enabled);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function available(OrderInterface $order) {
    if (!$this->isEnabled()) {
      return FALSE;
    }
    if (!$this->getPromotion()->available($order)) {
      return FALSE;
    }
    if ($usage_limit = $this->getUsageLimit()) {
      /** @var \Drupal\commerce_promotion\PromotionUsageInterface $usage */
      $usage = \Drupal::service('commerce_promotion.usage');
      if ($usage_limit <= $usage->loadByCoupon($this)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Ensure there's a reference on each promotion.
    $promotion = $this->getPromotion();
    if ($promotion && !$promotion->hasCoupon($this)) {
      $promotion->addCoupon($this);
      $promotion->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    // Delete the related usage.
    /** @var \Drupal\commerce_promotion\PromotionUsageInterface $usage */
    $usage = \Drupal::service('commerce_promotion.usage');
    $usage->deleteByCoupon($entities);
    // Delete references to those coupons in promotions.
    foreach ($entities as $coupon) {
      $coupons_id[] = $coupon->id();
    }
    $promotions = \Drupal::entityTypeManager()->getStorage('commerce_promotion')->loadByProperties(['coupons' => $coupons_id]);
    /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
    foreach ($promotions as $promotion) {
      foreach ($entities as $entity) {
        $promotion->removeCoupon($entity);
      }
      $promotion->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // The promotion backreference, populated by Promotion::postSave().
    $fields['promotion_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Promotion'))
      ->setDescription(t('The parent promotion.'))
      ->setSetting('target_type', 'commerce_promotion')
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Coupon code'))
      ->setDescription(t('The unique, machine-readable identifier for a coupon.'))
      ->addConstraint('CouponCode')
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['usage_limit'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Usage limit'))
      ->setDescription(t('The maximum number of times the coupon can be used. 0 for unlimited.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'commerce_usage_limit',
        'weight' => 4,
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('Whether the coupon is enabled.'))
      ->setDefaultValue(TRUE)
      ->setRequired(TRUE)
      ->setSettings([
        'on_label' => t('Enabled'),
        'off_label' => t('Disabled'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 0,
      ]);

    return $fields;
  }

}
