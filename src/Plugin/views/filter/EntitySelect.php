<?php

/**
 * @file
 * Contains \Drupal\commerce\Plugin\views\filter\EntitySelect.
 */

namespace Drupal\commerce\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
/**
 * Filters by entities.
 *
 * Can be configured to hide the exposed form when there's only one possible
 * entity.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("commerce_entity_select")
 */
class EntitySelect extends ManyToOne {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Get the field storage definition of current field.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface
   */
  protected $fieldStorage;

  /**
   * The entity Type the field target.
   *
   * @var string
   */
  protected $targetType;

  /**
   * Stores the count of entities.
   *
   * @var integer
   */
  protected $entityCount = 0;

  /**
   * Constructs a Bundle object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->fieldStorage = $this->entityManager->getFieldStorageDefinitions($this->getEntityType())[$this->realField];
    $this->targetType = $this->fieldStorage->getSetting('target_type');
    $entity_query = $this->entityManager->getStorage($this->targetType)->getQuery();
    $this->entityCount = $entity_query->count()->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultExposeOptions() {
    parent::defaultExposeOptions();
    $this->options['expose']['hide_single_entity'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['expose']['contains']['hide_single_entity'] = ['default' => TRUE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['expose']['hide_single_entity'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide if there\'s only one entity.'),
      '#default_value' => $this->options['expose']['hide_single_entity'],
    ];
  }

  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    // Use commerce_entity_select form element if entityCount is higher than
    // threshold.
    // Use selectbox by default.
    $threshold = ($this->fieldStorage->getSetting('autocomplete_threshold')) ? $this->fieldStorage->getSetting('autocomplete_threshold') : 2;
    if ($this->entityCount > $threshold) {
      $form['value'] = array(
        'label' => $this->fieldStorage->getLabel(),
        '#type' => 'commerce_entity_select',
        '#target_type' => $this->fieldStorage->getSetting('target_type'),
        '#multiple' => TRUE,
        '#default_value' => $this->value,
        '#autocomplete_threshold' => $threshold,
        '#autocomplete_size' => ($this->fieldStorage->getSetting('autocomplete_size')) ? $this->fieldStorage->getSetting('autocomplete_size') : 70,
        '#autocomplete_placeholder' => $this->fieldStorage->getSetting('autocomplete_placeholder'),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if ($this->entityCount < 7 && $this->entityCount > 1) {
      if (!isset($this->valueOptions)) {
        $options = [];

        $entities = $this->entityManager->getStorage($this->targetType)->loadMultiple();
        if (!empty($entities)) {
          foreach ($entities as $entity) {
            $options[$entity->id()] = $entity->label();
          }
        }

        $this->valueOptions = $options;
      }
    }

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function isExposed() {
    if (!empty($this->options['exposed'])) {
      if ($this->entityCount > 1 || empty($this->options['expose']['hide_single_entity'])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitExposed(&$form, FormStateInterface $form_state) {
    $filter_value = $form_state->getValue($this->options['id']);
    if (empty($filter_value)) {
      // The exposed form is submitted on first view load, even when this
      // filter is hidden. To prevent a notice, a default value is provided.
      $form_state->setValue($this->options['id'], 'All');
    }
  }

}
