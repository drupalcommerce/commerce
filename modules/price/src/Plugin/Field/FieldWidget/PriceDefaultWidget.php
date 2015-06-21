<?php

/**
 * @file
 * Contains \Drupal\commerce_price\Plugin\Field\FieldWidget\PriceDefaultWidget.
 */

namespace Drupal\commerce_price\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'price_default' widget.
 *
 * @FieldWidget(
 *   id = "price_default",
 *   label = @Translation("Price"),
 *   field_types = {
 *     "price"
 *   }
 * )
 */
class PriceDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * Constructs a new PriceDefaultWidget object.
   *
   * @param string $pluginId
   *   The plugin_id for the widget.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $thirdPartySettings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   */
  public function __construct($pluginId, $pluginDefinition, FieldDefinitionInterface $fieldDefinition, array $settings, array $thirdPartySettings, EntityManagerInterface $entityManager) {
    parent::__construct($pluginId, $pluginDefinition, $fieldDefinition, $settings, $thirdPartySettings);

    $this->currencyStorage = $entityManager->getStorage('commerce_currency');
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
      $configuration['third_party_settings'],
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Load both enabled and disabled currencies, prices with disabled
    // currencies can be skipped down the line.
    $currencies = $this->currencyStorage->loadMultiple();
    $currencyCodes = array_keys($currencies);
    // Stop rendering if there are no currencies available.
    if (empty($currencyCodes)) {
      return $element;
    }

    $element['#attached']['library'][] = 'commerce_price/admin';
    $element['amount'] = [
      '#type' => 'textfield',
      '#title' => $element['#title'],
      '#default_value' => $this->prepareAmount($items[$delta]->amount),
      '#required' => $element['#required'],
      '#size' => 10,
      '#maxlength' => 255,
    ];
    if (count($currencyCodes) == 1) {
      $lastVisibleElement = 'amount';
      $currencyCode = reset($currencyCodes);
      $element['amount']['#field_suffix'] = $currencyCode;
      $element['currency_code'] = [
        '#type' => 'value',
        '#value' => $currencyCode,
      ];
    }
    else {
      $lastVisibleElement = 'currency_code';
      $element['currency_code'] = [
        '#type' => 'select',
        '#title' => $this->t('Currency'),
        '#default_value' => $items[$delta]->currency_code,
        '#options' => array_combine($currencyCodes, $currencyCodes),
        '#title_display' => 'invisible',
        '#field_suffix' => '',
      ];
    }
    // Add the help text if specified.
    if (!empty($element['#description'])) {
      $element[$lastVisibleElement]['#field_suffix'] .= '<div class="description">' . $element['#description'] . '</div>';
    }

    return $element;
  }

  /**
   * Prepares the amount for display.
   *
   * @param int $amount
   *   The amount
   *
   * @return int
   *   The processed amount, ready for display.
   */
  protected function prepareAmount($amount) {
    $amount = $amount ?: 0;
    if (!empty($amount)) {
      // Trim redundant zeroes.
      $amount = rtrim($amount, 0);
      // If we've trimmed all the way to the decimal separator, remove it.
      $lastChar = substr($amount, -1, 1);
      if (!is_numeric($lastChar)) {
        $amount = substr($amount, 0, strlen($amount) - 1);
      }
    }

    return $amount;
  }

}
