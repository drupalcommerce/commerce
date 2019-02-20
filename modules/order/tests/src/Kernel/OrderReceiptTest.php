<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Language\Language;
use Drupal\Core\Site\Settings;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the sending of multilingual order receipt emails.
 *
 * @group commerce
 */
class OrderReceiptTest extends CommerceKernelTestBase {

  use AssertMailTrait;

  /**
   * A sample order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Custom translations.
   *
   * @var array
   */
  protected $customTranslations = [
    'fr' => [
      'Order #@number confirmed' => 'Commande #123456789 confirmée',
      'Thank you for your order!' => 'Nous vous remercions de votre commande!',
    ],
    'es' => [
      'Order #@number confirmed' => 'Pedido #123456789 confirmado',
      'Thank you for your order!' => '¡Gracias por su orden!',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
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
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product', 'commerce_order']);
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);

    foreach (array_keys($this->customTranslations) as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    // Provide the translated strings by overriding in-memory settings.
    $settings = Settings::getAll();
    foreach ($this->customTranslations as $langcode => $custom_translation) {
      foreach ($custom_translation as $untranslated => $translated) {
        $settings['locale_custom_strings_' . $langcode][''][$untranslated] = $translated;
      }
    }
    new Settings($settings);

    /** @var \Drupal\commerce_price\CurrencyImporterInterface $currency_importer */
    $currency_importer = $this->container->get('commerce_price.currency_importer');
    $currency_importer->importTranslations(array_keys($this->customTranslations));
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
      'name' => 'Tienda por defecto',
    ]);
    $this->store->addTranslation('fr', [
      'name' => 'Magasin par défaut',
    ]);
    $this->store->save();

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
   * @param string $expected_subject
   *   The expected subject.
   * @param string $expected_store_label
   *   The expected store label.
   * @param string $expected_thank_you_text
   *   The expected thank you text.
   * @param string $expected_order_total
   *   The expected order total.
   *
   * @dataProvider providerOrderReceiptMultilingualData
   */
  public function testOrderReceipt($langcode, $expected_langcode, $expected_subject, $expected_store_label, $expected_thank_you_text, $expected_order_total) {
    $customer = $this->order->getCustomer();
    $customer->set('preferred_langcode', $langcode);
    $customer->save();

    $this->order->setOrderNumber('123456789');
    $this->order->getState()->applyTransitionById('place');
    $this->order->save();

    $emails = $this->getMails();
    $email = reset($emails);
    $this->assertEquals($this->order->getEmail(), $email['to']);
    $this->assertEquals('bcc@example.com', $email['headers']['Bcc']);
    $this->assertEquals($expected_langcode, $email['langcode']);
    $this->assertEquals($expected_subject, $email['subject']);
    $this->assertContains($expected_store_label, $email['body']);
    $this->assertContains($expected_thank_you_text, $email['body']);
    $this->assertContains($expected_order_total, $email['body']);
  }

  /**
   * Provides data for the multilingual email receipt test.
   *
   * @return array
   *   The data.
   */
  public function providerOrderReceiptMultilingualData() {
    return [
      [NULL, 'en', 'Order #123456789 confirmed', 'Default store', 'Thank you for your order!', 'Order Total: $12.00'],
      [Language::LANGCODE_DEFAULT, 'en', 'Order #123456789 confirmed', 'Default store', 'Thank you for your order!', 'Order Total: $12.00'],
      ['es', 'es', $this->customTranslations['es']['Order #@number confirmed'], 'Tienda por defecto', $this->customTranslations['es']['Thank you for your order!'], 'Order Total: US$12.00'],
      ['fr', 'fr', $this->customTranslations['fr']['Order #@number confirmed'], 'Magasin par défaut', $this->customTranslations['fr']['Thank you for your order!'], 'Order Total: U$D12.00'],
      ['en', 'en', 'Order #123456789 confirmed', 'Default store', 'Thank you for your order!', 'Order Total: $12.00'],
    ];
  }

}
