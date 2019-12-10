<?php

namespace Drupal\commerce_number_pattern\Plugin\Commerce\NumberPattern;

use Drupal\commerce_number_pattern\Sequence;
use Drupal\commerce_store\Entity\EntityStoreInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for number pattern plugins which support sequences.
 */
abstract class SequentialNumberPatternBase extends NumberPatternBase implements SequentialNumberPatternInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new SequentialNumberPatternBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection, EntityTypeManagerInterface $entity_type_manager, LockBackendInterface $lock, TimeInterface $time, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $token);

    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->lock = $lock;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('lock'),
      $container->get('datetime.time'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'pattern' => '[pattern:number]',
      'initial_number' => 1,
      'padding' => 0,
      'per_store_sequence' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['initial_number'] = [
      '#type' => 'number',
      '#title' => $this->t('Initial number'),
      '#default_value' => $this->configuration['initial_number'],
      '#min' => 1,
    ];

    $checkbox_name = 'configuration[' . $this->pluginId . '][use_padding]';
    $form['use_padding'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use fixed length numbers'),
      '#default_value' => !empty($this->configuration['padding']),
    ];
    $form['padding'] = [
      '#type' => 'number',
      '#title' => $this->t('Total number of digits'),
      '#description' => $this->t('The number will be padded with leading zeroes. Example: a value of 4 will output 52 as 0052.'),
      '#default_value' => $this->configuration['padding'],
      '#min' => 0,
      '#states' => [
        'visible' => [
          ':input[name="' . $checkbox_name . '"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $entity_type_id = $form_state->getValue('targetEntityType');
    if (!empty($entity_type_id)) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      if ($entity_type->entityClassImplements(EntityStoreInterface::class)) {
        $form['per_store_sequence'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Generate sequences on a per-store basis'),
          '#description' => $this->t('Ensures that numbers are not shared between stores.'),
          '#default_value' => $this->configuration['per_store_sequence'],
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    if (strpos($values['pattern'], '[pattern:number]') === FALSE) {
      $form_state->setError($form['pattern'], $this->t('Missing the required token [pattern:number].'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['initial_number'] = $values['initial_number'];
      $this->configuration['padding'] = !empty($values['use_padding']) ? $values['padding'] : '0';
      $this->configuration['per_store_sequence'] = !empty($values['per_store_sequence']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function generate(ContentEntityInterface $entity) {
    $next_sequence = $this->getNextSequence($entity);
    $number = $next_sequence->getNumber();
    if ($this->configuration['padding'] > 0) {
      $number = str_pad($number, $this->configuration['padding'], '0', STR_PAD_LEFT);
    }
    $number = $this->token->replace($this->configuration['pattern'], [
      'pattern' => ['number' => $number],
      $entity->getEntityTypeId() => $entity,
    ]);

    return $number;
  }

  /**
   * {@inheritdoc}
   */
  public function getInitialSequence(ContentEntityInterface $entity) {
    return new Sequence([
      'number' => $this->configuration['initial_number'],
      'generated' => $this->time->getRequestTime(),
      'store_id' => $this->getStoreId($entity),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentSequence(ContentEntityInterface $entity) {
    $query = $this->connection->select('commerce_number_pattern_sequence', 'cnps');
    $query->fields('cnps', ['store_id', 'number', 'generated']);
    $query
      ->condition('entity_id', $this->parentEntity->id())
      ->condition('store_id', $this->getStoreId($entity));
    $result = $query->execute()->fetchAssoc();
    if (empty($result)) {
      return NULL;
    }

    return new Sequence([
      'number' => $result['number'],
      'generated' => $result['generated'],
      'store_id' => $result['store_id'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getNextSequence(ContentEntityInterface $entity) {
    $lock_name = 'commerce_number_pattern.plugin.' . $this->parentEntity->id();
    while (!$this->lock->acquire($lock_name)) {
      $this->lock->wait($lock_name);
    }

    $store_id = $this->getStoreId($entity);
    $current_sequence = $this->getCurrentSequence($entity);
    if (!$current_sequence || $this->shouldReset($current_sequence)) {
      $sequence = $this->getInitialSequence($entity);
    }
    else {
      $sequence = new Sequence([
        'number' => $current_sequence->getNumber() + 1,
        'generated' => $this->time->getRequestTime(),
        'store_id' => $store_id,
      ]);
    }
    $this->connection->merge('commerce_number_pattern_sequence')
      ->fields([
        'entity_id' => $this->parentEntity->id(),
        'store_id' => $store_id,
        'number' => $sequence->getNumber(),
        'generated' => $sequence->getGeneratedTime(),
      ])
      ->keys([
        'entity_id' => $this->parentEntity->id(),
        'store_id' => $store_id,
      ])
      ->execute();
    $this->lock->release($lock_name);

    return $sequence;
  }

  /**
   * {@inheritdoc}
   */
  public function resetSequence() {
    return $this->connection->delete('commerce_number_pattern_sequence')
      ->condition('entity_id', $this->parentEntity->id())
      ->execute();
  }

  /**
   * Gets whether the sequence should be reset.
   *
   * @param \Drupal\commerce_number_pattern\Sequence $current_sequence
   *   The current sequence.
   *
   * @return bool
   *   TRUE if the sequence should be reset, FALSE otherwise.
   */
  abstract protected function shouldReset(Sequence $current_sequence);

  /**
   * Gets the store_id to use for the sequence.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return int
   *   The store ID.
   */
  protected function getStoreId(ContentEntityInterface $entity) {
    $store_id = 0;
    if (!empty($this->configuration['per_store_sequence']) && $entity instanceof EntityStoreInterface) {
      $store_id = $entity->getStoreId();
    }

    return $store_id;
  }

}
