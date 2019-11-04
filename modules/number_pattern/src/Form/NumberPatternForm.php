<?php

namespace Drupal\commerce_number_pattern\Form;

use Drupal\commerce_number_pattern\NumberPatternManager;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity\Form\EntityDuplicateFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NumberPatternForm extends EntityForm {

  use EntityDuplicateFormTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The number pattern plugin manager.
   *
   * @var \Drupal\commerce_number_pattern\NumberPatternManager
   */
  protected $pluginManager;

  /**
   * Constructs a new NumberPatternForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_number_pattern\NumberPatternManager $plugin_manager
   *   The number pattern plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, NumberPatternManager $plugin_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_number_pattern')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_number_pattern\Entity\NumberPatternInterface $number_pattern */
    $number_pattern = $this->entity;
    $plugins = array_column($this->pluginManager->getDefinitions(), 'label', 'id');
    asort($plugins);

    // Use the first available plugin as the default value.
    if (!$number_pattern->getPluginId()) {
      $plugin_ids = array_keys($plugins);
      $plugin = reset($plugin_ids);
      $number_pattern->setPluginId($plugin);
    }
    // The form state will have a plugin value if #ajax was used.
    $plugin = $form_state->getValue('plugin', $number_pattern->getPluginId());
    $target_entity_type_id = $form_state->getValue('targetEntityType', $number_pattern->getTargetEntityTypeId());
    // Pass the plugin configuration only if the plugin hasn't been changed via #ajax.
    $plugin_configuration = $number_pattern->getPluginId() == $plugin ? $number_pattern->getPluginConfiguration() : [];

    $wrapper_id = Html::getUniqueId('number-pattern-form');
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';
    $form['#tree'] = TRUE;
    $entity_types = $this->entityTypeManager->getDefinitions();
    $options = [];
    foreach ($entity_types as $entity_type) {
      if ($entity_type->get('allow_number_patterns')) {
        $options[$entity_type->id()] = $entity_type->getLabel();
      }
    }
    if (empty($target_entity_type_id) && count($options) === 1) {
      // There's only one option, pre-select it.
      $option_ids = array_keys($options);
      $target_entity_type_id = reset($option_ids);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $number_pattern->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $number_pattern->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_number_pattern\Entity\NumberPattern::load',
      ],
      '#disabled' => !$number_pattern->isNew(),
    ];
    $form['targetEntityType'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#default_value' => $target_entity_type_id,
      '#options' => $options,
      '#required' => TRUE,
      '#disabled' => !$number_pattern->isNew(),
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => $wrapper_id,
      ],
      '#access' => count($options) > 1,
    ];
    if (!$target_entity_type_id) {
      return $form;
    }

    $form['plugin'] = [
      '#type' => 'radios',
      '#title' => $this->t('Number generation method'),
      '#options' => $plugins,
      '#default_value' => $plugin,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => $wrapper_id,
      ],
    ];
    $form['configuration'] = [
      '#type' => 'commerce_plugin_configuration',
      '#plugin_type' => 'commerce_number_pattern',
      '#plugin_id' => $plugin,
      '#default_value' => $plugin_configuration,
      "#entity_type" => $target_entity_type_id,
    ];

    return $form;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\commerce_number_pattern\Entity\NumberPatternInterface $number_pattern */
    $number_pattern = $this->entity;
    $number_pattern->setPluginConfiguration($form_state->getValue(['configuration']));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->postSave($this->entity, $this->operation);
    $this->messenger()->addMessage($this->t('Saved the %label number pattern.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_number_pattern.collection');
  }

}
