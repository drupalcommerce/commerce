<?php

namespace Drupal\commerce_log;

use Drupal\commerce_log\Plugin\LogTemplate\LogTemplate;
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
   * The log category manager.
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
    'class' => LogTemplate::class,
  ];

  /**
   * Constructs a new LogTemplateManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\commerce_log\LogCategoryManagerInterface $category_manager
   *   The log category manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend, LogCategoryManagerInterface $category_manager) {
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'commerce_log_template', ['commerce_log_template']);
    $this->categoryManager = $category_manager;
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

    if (!$this->isStringSafe($definition['template'])) {
      throw new PluginException(sprintf('The commerce_log_template %s does not have a valid template string.', $plugin_id));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelsByCategory($entity_type_id = NULL) {
    $definitions = $this->getSortedDefinitions();
    $category_labels = $this->getCategoryLabels($entity_type_id);
    $grouped_definitions = [];
    foreach ($definitions as $id => $definition) {
      $category_id = $definition['category'];
      if (!isset($category_labels[$category_id])) {
        // Don't return log templates for categories ignored due to their entity type.
        continue;
      }
      $category_label = $category_labels[$category_id];
      $grouped_definitions[$category_label][$id] = $definition['label'];
    }

    return $grouped_definitions;
  }

  /**
   * Gets the sorted commerce_log_template plugin definitions.
   *
   * @return array
   *   The commerce_log_template plugin definitions, sorted by category and label.
   */
  protected function getSortedDefinitions() {
    // Sort the plugins first by category, then by label.
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
   * Gets a list of category labels for the given entity type ID.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   A list of categories labels keyed by ID.
   */
  protected function getCategoryLabels($entity_type_id = NULL) {
    $category_definitions = $this->categoryManager->getDefinitionsByEntityType($entity_type_id);
    $category_labels = array_map(function ($category_definition) {
      return (string) $category_definition['label'];
    }, $category_definitions);
    natcasesort($category_labels);

    return $category_labels;
  }

  /**
   * Checks whether a string is safe for translation.
   *
   * A copy of locale_string_is_safe, made to avoid a dependency on Locale.
   *
   * @param string $string
   *   The string.
   *
   * @return bool
   *   TRUE if the string is safe, FALSE otherwise.
   *
   * @see locale_string_is_safe()
   */
  protected function isStringSafe($string) {
    $string = preg_replace('/\[[a-z0-9_-]+(:[a-z0-9_-]+)+\]/i', '', $string);
    return Html::decodeEntities($string) == Html::decodeEntities(Xss::filter($string, ['a', 'abbr', 'acronym', 'address', 'b', 'bdo', 'big', 'blockquote', 'br', 'caption', 'cite', 'code', 'col', 'colcategory', 'dd', 'del', 'dfn', 'dl', 'dt', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'ins', 'kbd', 'li', 'ol', 'p', 'pre', 'q', 'samp', 'small', 'span', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'tt', 'ul', 'var']));
  }

}
