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
 *   id = "price_currency",
 *   label = @Translation("Currency"),
 *   field_types = {
 *     "price"
 *   }
 * )
 */
class PriceCurrencyFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * Constructs a PriceFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityManagerInterface $entity_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->currencyStorage = $entity_manager->getStorage('commerce_currency');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
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
      'currency_mode' => FALSE,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['currency_mode'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Select the format to use.'),
      '#default_value' => $this->getSetting('currency_mode'),
      '#options' => $this->getOptions(),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    $settings = $this->getSettings();
    foreach ($items as $delta => $item) {
      if ($settings['currency_mode'] == 'code') {
        $elements[$delta] = array('#markup' => $item->currency_code);
      }
      elseif ($settings['currency_mode'] == 'symbol') {
        $currency = $this->currencyStorage->load($item->currency_code);
        $elements[$delta] = array('#markup' => $currency->getSymbol());
      }
      elseif ($settings['currency_mode'] == 'numeric') {
        $currency = $this->currencyStorage->load($item->currency_code);
        $elements[$delta] = array('#markup' => $currency->getNumericCode());
      }
      elseif ($settings['currency_mode'] == 'name') {
        $currency = $this->currencyStorage->load($item->currency_code);
        $elements[$delta] = array('#markup' => $currency->getName());
      }
    }

    return $elements;
  }

  /**
   * Returns the options for currency.
   *
   * @return \CommerceGuys\Intl\NumberFormat\NumberFormat|\CommerceGuys\Intl\NumberFormat\NumberFormatInterface
   *    The number format.
   */
  protected function getOptions() {
    return array('code' => 'Code (USD)', 'symbol' => 'Symbol ($)', 'numeric' => 'Numeric (840)', 'name' => 'Name (United States dollar)');
  }

}
