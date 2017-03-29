<?php

namespace Drupal\commerce_promotion\Plugin\Field\FieldWidget;

use Drupal\commerce_promotion\PromotionUsageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of 'commerce_usage_limit'.
 *
 * @FieldWidget(
 *   id = "commerce_usage_limit",
 *   label = @Translation("Usage limit"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class UsageLimitWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The promotion usage.
   *
   * @var \Drupal\commerce_promotion\PromotionUsageInterface
   */
  protected $usage;

  /**
   * Constructs a new UsageLimitWidget object.
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
   * @param \Drupal\commerce_promotion\PromotionUsageInterface $usage
   *   The promotion usage.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, PromotionUsageInterface $usage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->usage = $usage;
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
      $container->get('commerce_promotion.usage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'description' => 'This is tracked by customer email.',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['description'] = [
      '#type' => 'textfield',
      '#title' => t('Help text'),
      '#default_value' => $this->getSetting('description'),
      '#description' => t('Text that will be shown description of the field.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
    $promotion = $items[$delta]->getEntity();
    $value = isset($items[$delta]->value) ? $items[$delta]->value : NULL;
    $options = [
      0 => $this->t('Unlimited'),
      1 => $this->t('Limited number of uses'),
    ];
    $element['#type'] = 'fieldset';
    $element['limit'] = [
      '#type' => 'radios',
      '#title' => $this->t('Total available'),
      '#options' => $options,
      '#default_value' => $value ? 1 : 0,
    ];

    $usage = $this->usage->getUsage($promotion);
    $states_selector_name = $this->fieldDefinition->getName() . "[$delta][limit]";
    $description = $this->getSetting('description');
    $description .= '<div class="usage-number">';
    $description .= $this->t('This promotion was used @usage times.', ['@usage' => $usage]);
    $description .= '</div>';
    $element['usage_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit per customer'),
      '#default_value' => $value,
      '#description' => $description,
      '#states' => [
        'invisible' => [
          ':input[name="' . $states_selector_name . '"]' => ['value' => 0],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      if (empty($value['limit'])) {
        continue;
      }
      $new_values[$key] = $value['usage_limit'];
    }
    return $new_values;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_promotion' && $field_name == 'usage_limit';
  }

}
