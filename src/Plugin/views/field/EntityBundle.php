<?php

/**
 * @file
 * Contains \Drupal\commerce\Plugin\views\field\EntityBundle.
 */

namespace Drupal\commerce\Plugin\views\field;

use Drupal\views\Plugin\views\field\Field;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Displays the entity bundle.
 *
 * Can be configured to show nothing when there's only one possible bundle,
 * allowing the 'Hide empty column' table setting to hide the column.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_bundle")
 */
class EntityBundle extends Field {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a \Drupal\commerce\Plugin\views\field\EntityBundle object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The field formatter plugin manager.
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_plugin_manager
   *   The field formatter plugin manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field plugin type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   *  @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *    The entity type bundle info.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, FormatterPluginManager $formatter_plugin_manager, FieldTypePluginManagerInterface $field_type_plugin_manager, LanguageManagerInterface $language_manager, RendererInterface $renderer, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $formatter_plugin_manager, $field_type_plugin_manager, $language_manager, $renderer);
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('plugin.manager.field.formatter'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('language_manager'),
      $container->get('renderer'),
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
      '#title' => t('Hide if there\'s only one bundle.'),
      '#default_value' => $this->options['hide_single_bundle'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderItems($items) {
    if ($this->options['hide_single_bundle']) {
      $entity_type = $this->getEntityType();
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
      // Hide bundle name if there's only one bundle.
      if (count($bundles) == 1) {
        return '';
      }
    }
    return parent::renderItems($items);
  }

}
