<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Language\Language;
use Drupal\Core\Site\Settings;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\profile\Entity\Profile;

/**
 * Tests the sending of multilingual order receipt emails.
 *
 * @group commerce
 */
class OrderReceiptTest extends OrderKernelTestBase {

  use AssertMailTrait;

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Translated strings used in the order receipt.
   *
   * @var array
   */
  protected $translations = [
    'fr' => [
      'Order #@number confirmed' => 'Commande #@number confirmée',
      'Thank you for your order!' => 'Nous vous remercions de votre commande!',
      'Default store' => 'Magasin par défaut',
      'Cash on delivery' => 'Paiement à la livraison',
    ],
    'es' => [
      'Order #@number confirmed' => 'Pedido #@number confirmado',
      'Thank you for your order!' => '¡Gracias por su orden!',
      'Default store' => 'Tienda por defecto',
      'Cash on delivery' => 'Contra reembolso',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_payment',
    'language',
    'locale',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['language']);
    $this->installSchema('locale', ['locales_source', 'locales_target', 'locales_location']);
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

    foreach (array_keys($this->translations) as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    // Provide the translated strings by overriding in-memory settings.
    $settings = Settings::getAll();
    foreach ($this->translations as $langcode => $custom_translation) {
      foreach ($custom_translation as $untranslated => $translated) {
        $settings['locale_custom_strings_' . $langcode][''][$untranslated] = $translated;
      }
    }
    new Settings($settings);

    /** @var \Drupal\commerce_price\CurrencyImporterInterface $currency_importer */
    $currency_importer = $this->container->get('commerce_price.currency_importer');
    $currency_importer->importTranslations(array_keys($this->translations));
    /** @var \Drupal\language\ConfigurableLanguageManagerInterface $language_manager */
    $language_manager = $this->container->get('language_manager');
    // The translated USD symbol is $US in both French and Spanish.
    // Invent a new symbol translation for French, to test translations.
    $fr_usd = $language_manager->getLanguageConfigOverride('fr', 'commerce_price.commerce_currency.USD');
    $fr_usd->set('symbol', 'U$D');
    $fr_usd->save();

    $order_type = OrderType::load('default');
    $order_type->setReceiptBcc('bcc@example.com');
    $order_type->save();

    $this->store = $this->reloadEntity($this->store);
    $this->store->addTranslation('es', [
      'name' => $this->translations['es']['Default store'],
    ]);
    $this->store->addTranslation('fr', [
      'name' => $this->translations['fr']['Default store'],
    ]);
    $this->store->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = PaymentGateway::create([
      'id' => 'cod',
      'label' => 'Manual',
      'plugin' => 'manual',
      'configuration' => [
        'display_label' => 'Cash on delivery',
        'instructions' => [
          'value' => 'Sample payment instructions.',
          'format' => 'plain_text',
        ],
      ],
    ]);
    $payment_gateway->save();
    $es_payment_gateway = $language_manager->getLanguageConfigOverride('es', 'commerce_payment.commerce_payment_gateway.cod');
    $es_payment_gateway->set('configuration', [
      'display_label' => $this->translations['es']['Cash on delivery'],
    ]);
    $es_payment_gateway->save();
    $fr_payment_gateway = $language_manager->getLanguageConfigOverride('fr', 'commerce_payment.commerce_payment_gateway.cod');
    $fr_payment_gateway->set('configuration', [
      'display_label' => $this->translations['fr']['Cash on delivery'],
    ]);
    $fr_payment_gateway->save();

    $product = Product::create([
      'type' => 'default',
      'title' => 'Default testing product',
    ]);
    $product->save();

    $variation1 = ProductVariation::create([
      'type' => 'default',
      'product_id' => $product->id(),
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'status' => 1,
      'price' => new Price('12.00', 'USD'),
    ]);
    $variation1->save();

    $profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
      'uid' => $user->id(),
    ]);
    $profile->save();
    $profile = $this->reloadEntity($profile);

    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');
    $order_item1 = $order_item_storage->createFromPurchasableEntity($variation1);
    $order_item1->save();

    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'billing_profile' => $profile,
      'store_id' => $this->store->id(),
      'order_items' => [$order_item1],
      'payment_gateway' => $payment_gateway->id(),
    ]);
    $order->save();
    $this->order = $this->reloadEntity($order);
  }

  /**
   * Tests disabling the order receipt.
   */
  public function testOrderReceiptDisabled() {
    $order_type = OrderType::load($this->order->bundle());
    $order_type->setSendReceipt(FALSE);
    $order_type->save();

    $this->order->getState()->applyTransitionById('place');
    $this->order->save();

    $this->assertCount(0, $this->getMails());
  }

  /**
   * Tests that the email is sent and translated to the customer's language.
   *
   * The email is sent in the customer's langcode  if the user is not anonymous,
   * otherwise it is the site's default langcode. In #2603482 this could
   * be changed to use the order's langcode.
   *
   * @param string $langcode
   *   The langcode to test with.
   * @param string $expected_langcode
   *   The expected langcode.
   * @param string $expected_order_total
   *   The expected order total.
   *
   * @dataProvider providerOrderReceiptMultilingualData
   */
  public function testOrderReceipt($langcode, $expected_langcode, $expected_order_total) {
    $customer = $this->order->getCustomer();
    $customer->set('preferred_langcode', $langcode);
    $customer->save();

    $this->order->setOrderNumber('123456789');
    $this->order->getState()->applyTransitionById('place');
    $this->order->save();

    if (isset($this->translations[$expected_langcode])) {
      $strings = $this->translations[$expected_langcode];
    }
    else {
      // Use the untranslated strings.
      $strings = array_keys($this->translations['fr']);
      $strings = array_combine($strings, $strings);
    }
    $subject = new FormattableMarkup($strings['Order #@number confirmed'], [
      '@number' => $this->order->getOrderNumber(),
    ]);

    $emails = $this->getMails();
    $email = reset($emails);
    $this->assertEquals($this->order->getEmail(), $email['to']);
    $this->assertEquals('bcc@example.com', $email['headers']['Bcc']);
    $this->assertEquals($expected_langcode, $email['langcode']);

    $this->assertStringContainsString((string) $subject, $email['subject']);
    $this->assertStringContainsString($strings['Thank you for your order!'], $email['body']);
    $this->assertStringContainsString($strings['Default store'], $email['body']);
    $this->assertStringContainsString($strings['Cash on delivery'], $email['body']);
    $this->assertStringContainsString('Order Total: ' . $expected_order_total, $email['body']);
  }

  /**
   * Provides data for the multilingual email receipt test.
   *
   * @return array
   *   The data.
   */
  public function providerOrderReceiptMultilingualData() {
    return [
      [NULL, 'en', '$12.00'],
      [Language::LANGCODE_DEFAULT, 'en', '$12.00'],
      ['es', 'es', 'US$12.00'],
      ['fr', 'fr', 'U$D12.00'],
      ['en', 'en', '$12.00'],
    ];
  }

}
