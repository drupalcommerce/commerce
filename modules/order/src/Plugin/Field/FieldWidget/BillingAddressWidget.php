<?php

/**
 * @file
 * Contains \Drupal\commerce_order\Plugin\Field\FieldWidget\BillingAddressWidget.
 */

namespace Drupal\commerce_order\Plugin\Field\FieldWidget;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\profile\ProfileStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'billing_address_options' widget.
 *
 * @FieldWidget(
 *   id = "billing_address_options",
 *   label = @Translation("Billing Addresses"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class BillingAddressWidget extends OptionsWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;


  /**
   * The entity query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Constructs a BillingAddressWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
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
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element = parent::formElement($items, $delta, $element, $form, $form_state);;

    $selected = $this->getSelectedOptions($items);
    $addresses = $this->getBillingAddresses($items);

    // If required and there is one single option, preselect it.
    if ($this->required && count($addresses) == 1) {
      reset($addresses);
      $selected = [key($addresses)];
    }

    $element += [
      '#type' => ($this->multiple) ? 'checkboxes' : 'radios',
      '#default_value' => $selected ? reset($selected) : NULL,
      '#options' => $addresses,
    ];

    return $element;
  }

  /**
   * Returns billing address items.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Array of default values for this field.
   *
   * @return array
   *   An array of markup for the radio buttons.
   */
  protected function getBillingAddresses(FieldItemListInterface $items) {
    $profiles = $this->entityTypeManager->getStorage('profile')
      ->loadMultipleByUser($items->getEntity()->getOwner(), 'billing');

    $options = [];

    $view_builder = $this->entityTypeManager->getViewBuilder('profile');
    foreach ($profiles as $profile) {
      $renderable = $view_builder->view($profile);
      $options[$profile->id()] = $this->renderer->render($renderable);
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    return NULL;
  }
}
