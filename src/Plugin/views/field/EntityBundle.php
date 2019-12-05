<?php

namespace Drupal\commerce\Plugin\views\field;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the entity bundle.
 *
 * Can be configured to show nothing when there's only one possible bundle.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_entity_bundle")
 */
class EntityBundle extends EntityField {

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new EntityBundle object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_plugin_manager
   *   The field formatter plugin manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field plugin type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, FormatterPluginManager $formatter_plugin_manager, FieldTypePluginManagerInterface $field_type_plugin_manager, LanguageManagerInterface $language_manager, RendererInterface $renderer, EntityRepositoryInterface $entity_repository, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $formatter_plugin_manager, $field_type_plugin_manager, $language_manager, $renderer, $entity_repository, $entity_field_manager);

    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field.formatter'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('entity.repository'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['hide_single_bundle'] = ['default' => TRUE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['hide_single_bundle'] = [
      '#type' => 'checkbox',
      '#title' => t("Hide if there's only one bundle."),
      '#default_value' => $this->options['hide_single_bundle'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($this->getEntityType());
    if ($this->options['hide_single_bundle'] && count($bundles) <= 1) {
      return FALSE;
    }

    return parent::access($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    $items = parent::getItems($values);
    // Show the bundle label for entity types which don't use a bundle
    // entity type (i.e. they use bundle plugins).
    $entity_type_id = $this->getEntityType();
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if (!$entity_type->getBundleEntityType()) {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
      foreach ($items as &$item) {
        $bundle = $item['raw']->get('value')->getValue();
        if (isset($bundles[$bundle])) {
          $item['rendered']['#context']['value'] = $bundles[$bundle]['label'];
        }
      }
    }

    return $items;
  }

}
