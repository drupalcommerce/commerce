<?php

namespace Drupal\commerce_log;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

class LogViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    $build_list = [
      '#sorted' => TRUE,
    ];
    $weight = 0;
    /** @var \Drupal\commerce_log\Entity\LogInterface $entity */
    foreach ($entities as $key => $entity) {
      $build_list[$key] = [
        '#type' => 'inline_template',
        '#template' => $entity->getTemplate()->getTemplate(),
        '#context' => $entity->getParams(),
        // Collect cache defaults for this entity.
        '#cache' => [
          'tags' => Cache::mergeTags($this->getCacheTags(), $entity->getCacheTags()),
          'contexts' => $entity->getCacheContexts(),
          'max-age' => $entity->getCacheMaxAge(),
        ],
      ];
      // Give templates access to the source entity.
      $source_type = str_replace('commerce_', '', $entity->getSourceEntityTypeId());
      $build_list[$key]['#context'][$source_type] = $entity->getSourceEntity();

      $entityType = $this->entityTypeId;
      $this->moduleHandler()->alter([$entityType . '_build', 'entity_build'], $build_list[$key], $entity, $view_mode);

      $build_list[$key]['#weight'] = $weight++;
    }

    return $build_list;
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = $this->viewMultiple([$entity], $view_mode, $langcode);
    return $build[0];
  }

}
