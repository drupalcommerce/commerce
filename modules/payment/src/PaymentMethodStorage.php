<?php

namespace Drupal\commerce_payment;

use Drupal\commerce\CommerceContentEntityStorage;
use Drupal\commerce\TimeInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
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
   * @var \Drupal\commerce\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new CommerceContentEntityStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce\TimeInterface $time
   *   The time.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, EventDispatcherInterface $event_dispatcher, TimeInterface $time) {
    parent::__construct($entity_type, $database, $entity_manager, $cache, $language_manager, $event_dispatcher);
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('event_dispatcher'),
      $container->get('commerce.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadReusable(UserInterface $account, PaymentGatewayInterface $payment_gateway, StoreInterface $store = NULL) {
    if (!($payment_gateway->getPlugin() instanceof SupportsStoredPaymentMethodsInterface)) {
      return [];
    }

    $query = $this->getQuery()
      ->condition('uid', $account->id())
      ->condition('payment_gateway', $payment_gateway->id())
      ->condition('reusable', TRUE)
      ->condition('expires', $this->time->getRequestTime(), '>')
      ->sort('created', 'DESC');
    $result = $query->execute();
    if (empty($result)) {
      return [];
    }

    // Check if a store was passed and it has billing country restrictions.
    // @todo Review and refactor for proper entity query condition as billing_profile.entity.address.country_code
    // @see https://www.drupal.org/node/2822359
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface[] $payment_methods */
    $payment_methods = $this->loadMultiple($result);
    if ($store && !empty($store->getBillingCountries())) {
      $store_billing_countries = $store->getBillingCountries();
      foreach ($payment_methods as $key => $payment_method) {
        $country_code = $payment_method->getBillingProfile()->address->first()->getCountryCode();
        if (!in_array($country_code, $store_billing_countries)) {
          unset($payment_methods[$key]);
        }
      }
    }

    return $payment_methods;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    if (!isset($values['payment_gateway'])) {
      throw new EntityStorageException('Missing "payment_gateway" property when creating a payment method.');
    }

    return parent::doCreate($values);
  }

}
