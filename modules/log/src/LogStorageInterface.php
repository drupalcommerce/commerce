<?php

namespace Drupal\commerce_log;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;

interface LogStorageInterface extends SqlEntityStorageInterface {

  /**
   * Generates a log.
   *
   * @param \Drupal\Core\Entity\EntityInterface $source
   *   The source entity.
   * @param string $log_template_id
   *   The template ID.
   * @param array $params
   *   An array of params for the log.
   *
   * @return \Drupal\commerce_log\Entity\LogInterface
   *   The generated, unsaved, log.
   */
  public function generate(EntityInterface $source, $log_template_id, array $params);

  /**
   * Loads all logs for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\commerce_log\Entity\LogInterface[]
   *   An array of log entities.
   */
  public function loadByEntity(EntityInterface $entity);

}
