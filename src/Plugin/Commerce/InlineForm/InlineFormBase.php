<?php

namespace Drupal\commerce\Plugin\Commerce\InlineForm;

use Drupal\commerce\AjaxFormTrait;
use Drupal\commerce\Element\CommerceElementTrait;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base class for inline forms.
 */
abstract class InlineFormBase extends PluginBase implements InlineFormInterface, ContainerFactoryPluginInterface {

  use AjaxFormTrait;

  /**
   * Constructs a new InlineFormBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    $this->validateConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * Gets the required configuration for this plugin.
   *
   * @return string[]
   *   The required configuration keys.
   */
  protected function requiredConfiguration() {
    return [];
  }

  /**
   * Validates configuration.
   *
   * @throws \RuntimeException
   *   Thrown if a configuration value is invalid.
   */
  protected function validateConfiguration() {
    foreach ($this->requiredConfiguration() as $key) {
      if (empty($this->configuration[$key])) {
        throw new \RuntimeException(sprintf('The "%s" plugin requires the "%s" configuration key', $this->pluginId, $key));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildInlineForm(array $inline_form, FormStateInterface $form_state) {
    $inline_form['#theme_wrappers'] = ['container'];
    // Workaround for core bug #2897377.
    $inline_form['#id'] = Html::getId('edit-' . implode('-', $inline_form['#parents']));
    // Automatically validate and submit inline forms.
    $inline_form['#inline_form'] = $this;
    $inline_form['#process'][] = [CommerceElementTrait::class, 'attachElementSubmit'];
    $inline_form['#element_validate'][] = [CommerceElementTrait::class, 'validateElementSubmit'];
    $inline_form['#element_validate'][] = [get_class($this), 'runValidate'];
    $inline_form['#commerce_element_submit'][] = [get_class($this), 'runSubmit'];
    // Allow inline forms to modify the page title.
    $inline_form['#process'][] = [get_class($this), 'updatePageTitle'];

    return $inline_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateInlineForm(array &$inline_form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitInlineForm(array &$inline_form, FormStateInterface $form_state) {}

  /**
   * Runs the inline form validation.
   *
   * @param array $inline_form
   *   The inline form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function runValidate(array &$inline_form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\InlineFormInterface $plugin */
    $plugin = $inline_form['#inline_form'];
    $plugin->validateInlineForm($inline_form, $form_state);
  }

  /**
   * Runs the inline form submission.
   *
   * @param array $inline_form
   *   The inline form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function runSubmit(array &$inline_form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\InlineFormInterface $plugin */
    $plugin = $inline_form['#inline_form'];
    $plugin->submitInlineForm($inline_form, $form_state);
  }

  /**
   * Updates the page title based on the inline form's #page_title property.
   *
   * @param array $inline_form
   *   The inline form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The form element.
   */
  public static function updatePageTitle(array &$inline_form, FormStateInterface $form_state, array &$complete_form) {
    if (!empty($inline_form['#page_title'])) {
      $complete_form['#title'] = $inline_form['#page_title'];
    }
    return $inline_form;
  }

}
