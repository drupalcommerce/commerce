<?php

namespace Drupal\commerce_payment;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the payment method storage.
 */
class PaymentMethodStorage extends CommerceContentEntityStorage implements PaymentMethodStorageInterface {

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new PaymentMethodStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityFieldManagerInterface $entity_field_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, TimeInterface $time) {
    parent::__construct($entity_type, $database, $entity_field_manager, $cache, $language_manager, $memory_cache, $entity_type_bundle_info, $entity_type_manager, $event_dispatcher);

    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity_field.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadReusable(UserInterface $account, PaymentGatewayInterface $payment_gateway, array $billing_countries = []) {
    // Anonymous users cannot have reusable payment methods.
    if ($account->isAnonymous()) {
      return [];
    }
    if (!($payment_gateway->getPlugin() instanceof SupportsStoredPaymentMethodsInterface)) {
      return [];
    }

    $query = $this->getQuery();
    $query
      ->condition('uid', $account->id())
      ->condition('payment_gateway', $payment_gateway->id())
      ->condition('payment_gateway_mode', $payment_gateway->getPlugin()->getMode())
      ->condition('reusable', TRUE)
      ->condition($query->orConditionGroup()
        ->condition('expires', $this->time->getRequestTime(), '>')
        ->condition('expires', 0))
      ->sort('method_id', 'DESC');
    $result = $query->execute();
    if (empty($result)) {
      return [];
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface[] $payment_methods */
    $payment_methods = $this->loadMultiple($result);
    if (!empty($billing_countries) && $payment_gateway->getPlugin()->collectsBillingInformation()) {
      // Filter out payment methods that don't match the billing countries.
      // @todo Use a query condition once #2822359 is fixed.
      foreach ($payment_methods as $id => $payment_method) {
        $country_code = 'ZZ';
        if ($billing_profile = $payment_method->getBillingProfile()) {
          $country_code = $billing_profile->address->first()->getCountryCode();
        }

        if (!in_array($country_code, $billing_countries)) {
          unset($payment_methods[$id]);
        }
      }
    }

    return $payment_methods;
  }

  /**
   * {@inheritdoc}
   */
  public function createForCustomer($payment_method_type, $payment_gateway_id, $customer_id, ProfileInterface $billing_profile = NULL) {
    return $this->create([
      'type' => $payment_method_type,
      'payment_gateway' => $payment_gateway_id,
      'uid' => $customer_id,
      'billing_profile' => $billing_profile,
    ]);
  }

}
