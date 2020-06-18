<?php

namespace Drupal\commerce_promotion\Plugin\Field\FieldWidget;

use Drupal\commerce_promotion\Entity\CouponInterface;
use Drupal\commerce_promotion\Entity\PromotionInterface;
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
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : NULL;
    $field_name = $this->fieldDefinition->getName();

    if ($field_name == 'usage_limit_customer') {
      $title = $this->t('Total per customer');
      $default_count = 1;
      $radios_field = 'limit_customer';
      $description = '';
    }
    else {
      $title = $this->t('Total available');
      $default_count = 10;
      $radios_field = 'limit';
      $usage = 0;
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $items[$delta]->getEntity();
      if (!$entity->isNew()) {
        if ($entity instanceof PromotionInterface) {
          $usage = $this->usage->load($entity);
        }
        elseif ($entity instanceof CouponInterface) {
          $usage = $this->usage->loadByCoupon($entity);
        }
      }
      $formatted_usage = $this->formatPlural($usage, '1 use', '@count uses');
      $description = $this->t('Current usage: @usage.', ['@usage' => $formatted_usage]);
    }

    $radio_parents = array_merge($form['#parents'], [$field_name, 0, $radios_field]);
    $radio_path = array_shift($radio_parents);
    $radio_path .= '[' . implode('][', $radio_parents) . ']';

    $element[$radios_field] = [
      '#type' => 'radios',
      '#title' => $title,
      '#options' => [
        0 => $this->t('Unlimited'),
        1 => $this->t('Limited number of uses'),
      ],
      '#default_value' => $value ? 1 : 0,
    ];
    $element[$field_name] = [
      '#type' => 'number',
      '#title' => $this->t('Number of uses'),
      '#title_display' => 'invisible',
      '#default_value' => $value ?: $default_count,
      '#description' => $description,
      '#states' => [
        'invisible' => [
          ':input[name="' . $radio_path . '"]' => ['value' => 0],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $radios_field = ($field_name == 'usage_limit_customer') ? 'limit_customer' : 'limit';
    $new_values = [];
    foreach ($values as $key => $value) {
      if (empty($value[$radios_field])) {
        continue;
      }
      $new_values[$key] = $value[$field_name];
    }
    return $new_values;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    $applicable_entity_type = in_array($entity_type, ['commerce_promotion', 'commerce_promotion_coupon']);
    $applicable_field_name = in_array($field_name, ['usage_limit', 'usage_limit_customer']);
    return $applicable_entity_type && $applicable_field_name;
  }

}
