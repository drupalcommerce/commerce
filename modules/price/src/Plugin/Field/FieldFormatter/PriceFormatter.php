<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Plugin\field\formatter\PriceFormatter.
 */

namespace Drupal\commerce_price\Plugin\Field\FieldFormatter;

use CommerceGuys\Intl\Formatter\NumberFormatter;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the commerce price formatter.
 *
 * @FieldFormatter(
 *   id = "price",
 *   label = @Translation("Price"),
 *   field_types = {
 *     "price"
 *   }
 * )
 */
class PriceFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * The number format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $numberFormatterStorage;

  /**
   * Constructs a PriceFormatter object.
   *
   * @param string $pluginId
   *   The plugin_id for the formatter.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $viewMode
   *   The view mode.
   * @param array $thirdPartySettings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   */
  public function __construct($pluginId, $pluginDefinition, FieldDefinitionInterface $fieldDefinition, array $settings, $label, $viewMode, array $thirdPartySettings, EntityManagerInterface $entityManager) {
    parent::__construct($pluginId, $pluginDefinition, $fieldDefinition, $settings, $label, $viewMode, $thirdPartySettings);

    $this->currencyStorage = $entityManager->getStorage('commerce_currency');
    $this->numberFormatterStorage = $entityManager->getStorage('commerce_number_format');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $pluginId,
      $pluginDefinition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'show_currency_code' => FALSE,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['show_currency_code'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display the currency code instead of the currency symbol.'),
      '#default_value' => $this->getSetting('show_currency_code'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    $format = $this->getNumberFormat();
    $displayMode = $this->getSetting('show_currency_code') ? NumberFormatter::CURRENCY_DISPLAY_CODE : NumberFormatter::CURRENCY_DISPLAY_SYMBOL;

    $numberFormatter = new NumberFormatter($format, NumberFormatter::CURRENCY);
    $numberFormatter->setCurrencyDisplay($displayMode);

    foreach ($items as $delta => $item) {
      $currency = $this->currencyStorage->load($item->currency_code);
      $elements[$delta] = array('#markup' => $numberFormatter->formatCurrency($item->amount, $currency));
    }

    return $elements;
  }

  /**
   * Returns the number format for the current language.
   *
   * @return \CommerceGuys\Intl\NumberFormat\NumberFormat|\CommerceGuys\Intl\NumberFormat\NumberFormatInterface
   *    The number format.
   */
  protected function getNumberFormat() {
    $language = \Drupal::languageManager()->getCurrentLanguage();

    // @TODO a fallback number format provided us or configured by the user.
    $numberFormat = $this->numberFormatterStorage->load($language->getId());

    return $numberFormat;
  }

}
