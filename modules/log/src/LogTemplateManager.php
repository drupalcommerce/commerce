<?php

namespace Drupal\commerce_log;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * Manages discovery and instantiation of commerce_log_template plugins.
 *
 * @see plugin_api
 */
class LogTemplateManager extends DefaultPluginManager implements LogTemplateManagerInterface {

  /**
   * The commerce_log_category group manager.
   *
   * @var \Drupal\commerce_log\LogCategoryManagerInterface
   */
  protected $categoryManager;

  /**
   * Default values for each commerce_log_template plugin.
   *
   * @var array
   */
  protected $defaults = [
    'id' => '',
    'label' => '',
    'template' => '',
    'category' => '',
    'class' => 'Drupal\commerce_log\Plugin\LogTemplate\LogTemplate',
  ];

  /**
   * Constructs a new LogTemplateManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend, LogCategoryManagerInterface $group_manager) {
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'commerce_log_template', ['commerce_log_template']);
    $this->categoryManager = $group_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('commerce_log_templates', $this->moduleHandler->getModuleDirectories());
      $this->discovery->addTranslatableProperty('label', 'label_context');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    $definition['id'] = $plugin_id;
    foreach (['label', 'category', 'template'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The commerce_log_template %s must define the %s property.', $plugin_id, $required_property));
      }
    }

    if (!$this->templateStringIsSafe($definition['template'])) {
      throw new PluginException(sprintf('The log template %s does not have a valid template string', $plugin_id));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupedLabels($entity_type_id = NULL) {
    $definitions = $this->getSortedDefinitions();
    $group_labels = $this->getGroupLabels($entity_type_id);
    $grouped_definitions = [];
    foreach ($definitions as $id => $definition) {
      $group_id = $definition['category'];
      if (!isset($group_labels[$group_id])) {
        // Don't return log templates for groups ignored due to their entity type.
        continue;
      }
      $group_label = $group_labels[$group_id];
      $grouped_definitions[$group_label][$id] = $definition['label'];
    }

    return $grouped_definitions;
  }

  /**
   * Gets the sorted commerce_log_template plugin definitions.
   *
   * @return array
   *   The commerce_log_template plugin definitions, sorted by group and label.
   */
  protected function getSortedDefinitions() {
    // Sort the plugins first by group, then by label.
    $definitions = $this->getDefinitions();
    uasort($definitions, function ($a, $b) {
      if ($a['category'] != $b['category']) {
        return strnatcasecmp($a['category'], $b['category']);
      }
      return strnatcasecmp($a['label'], $b['label']);
    });

    return $definitions;
  }

  /**
   * Gets a list of category labels for the given entity type id.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return array
   *   A list of categories labels keyed by id.
   */
  protected function getGroupLabels($entity_type_id = NULL) {
    $group_definitions = $this->categoryManager->getDefinitionsByEntityType($entity_type_id);
    $group_labels = array_map(function ($group_definition) {
      return (string) $group_definition['label'];
    }, $group_definitions);
    natcasesort($group_labels);

    return $group_labels;
  }

  /**
   * Check that a string is safe to be used as log template.
   *
   * This is a direct copy of locale_string_is_safe to determine if the
   * template is XSS filter safe and able to be translated without directly
   * depending on the locale module.
   *
   * @param string $string
   *   The template.
   *
   * @return bool
   *   TRUE if the string is safe, FALSE otherwise.
   *
   * @see locale_string_is_safe()
   */
  protected function templateStringIsSafe($string) {
    $string = preg_replace('/\[[a-z0-9_-]+(:[a-z0-9_-]+)+\]/i', '', $string);
    return Html::decodeEntities($string) == Html::decodeEntities(Xss::filter($string, ['a', 'abbr', 'acronym', 'address', 'b', 'bdo', 'big', 'blockquote', 'br', 'caption', 'cite', 'code', 'col', 'colgroup', 'dd', 'del', 'dfn', 'dl', 'dt', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'ins', 'kbd', 'li', 'ol', 'p', 'pre', 'q', 'samp', 'small', 'span', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'tt', 'ul', 'var']));
  }

}
