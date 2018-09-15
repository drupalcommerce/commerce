<?php

namespace Drupal\commerce_log;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

interface LogStorageInterface extends ContentEntityStorageInterface {

  /**
   * Generates a log.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $source
   *   The source entity.
   * @param string $template_id
   *   The template ID.
   * @param array $params
   *   An array of params for the log.
   *
   * @return \Drupal\commerce_log\Entity\LogInterface
   *   The generated log, unsaved.
   */
  public function generate(ContentEntityInterface $source, $template_id, array $params = []);

  /**
   * Loads all logs for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\commerce_log\Entity\LogInterface[]
   *   The logs.
   */
  public function loadMultipleByEntity(ContentEntityInterface $entity);

}
