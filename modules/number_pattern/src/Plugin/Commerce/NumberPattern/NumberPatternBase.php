<?php

namespace Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for number pattern plugins.
 */
abstract class NumberPatternBase extends PluginBase implements NumberPatternInterface, ContainerFactoryPluginInterface {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The parent config entity.
   *
   * Not available while the plugin is being configured.
   *
   * @var \Drupal\commerce_number_pattern\Entity\NumberPatternInterface
   */
  protected $parentEntity;

  /**
   * Constructs a new NumberPatternBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->token = $token;
    if (array_key_exists('_entity', $configuration)) {
      $this->parentEntity = $configuration['_entity'];
      unset($configuration['_entity']);
    }
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token')
    );
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
    return [
      'pattern' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $entity_type_id = $form_state->getValue('targetEntityType');
    $token_types = ['pattern'];
    if ($entity_type_id) {
      $token_types[] = $entity_type_id;
    }

    $form['pattern'] = [
      '#title' => $this->t('Pattern'),
      '#type' => 'textfield',
      '#description' => $this->t('Allows adding a prefix (such as "INV-") or a suffix to the number.'),
      '#default_value' => $this->configuration['pattern'],
      '#required' => TRUE,
      '#element_validate' => ['token_element_validate'],
      '#token_types' => $token_types,
    ];
    $form['pattern_help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => $token_types,
      '#global_types' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      $this->configuration = [];
      $this->configuration['pattern'] = $values['pattern'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function generate(ContentEntityInterface $entity) {
    $number = $this->token->replace($this->configuration['pattern'], [
      'pattern' => [],
      $entity->getEntityTypeId() => $entity,
    ]);

    return $number;
  }

}
