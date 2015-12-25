<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Plugin\Field\FieldFormatter\AddToCartFormatter.
 */

namespace Drupal\commerce_product\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'add_to_cart' formatter.
 *
 * @FieldFormatter(
 *   id = "add_to_cart",
 *   label = @Translation("Add to cart form"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class AddToCartFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs an AddToCartFormatter object.
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
   *   Any third party settings.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, FormBuilderInterface $form_builder) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'show_quantity' => FALSE,
      'default_quantity' => 1,
      'combine' => TRUE,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['show_quantity'] = [
      '#type' => 'checkbox',
      '#title' => t('Display a quantity input field on the add to cart form.'),
      '#default_value' => $this->getSetting('show_quantity'),
    ];
    $form['default_quantity'] = [
      '#type' => 'number',
      '#title' => t('Default quantity'),
      '#default_value' => $this->getSetting('default_quantity'),
      '#min' => 1,
      '#max' => 9999,
    ];
    $form['combine'] = [
      '#type' => 'checkbox',
      '#title' => t('Attempt to combine line items containing the same product variation.'),
      '#description' => t('The line item type, referenced product variation, and data from fields exposed on the Add to Cart form must all match to combine.'),
      '#default_value' => $this->getSetting('combine'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // @todo.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // @see \Drupal\Core\Field\FormatterPluginManager::createInstance().
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // @todo Use a lazy_builder.
    $product = $items->getEntity();
    $settings = $this->getSettings();
    return $this->formBuilder->getForm('\Drupal\commerce_product\Form\AddToCartForm', $product, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return \Drupal::moduleHandler()->moduleExists('commerce_cart');
  }
}
