<?php

/**
 * @file
 * Contains Drupal\commerce\CommerceConfigBundleBase.
 */

namespace Drupal\commerce;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * CommerceConfigEntityBundleBase is an ancestor to the Commerce Type classes.
 */
class CommerceConfigEntityBundleBase extends ConfigEntityBundleBase implements CommerceEntityTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function getContentCount() {
    $instance_type = $this->getEntityType()->getBundleOf();
    $query = $this->entityManager()
      ->getListBuilder($instance_type)
      ->getStorage()
      ->getQuery();

    $count = $query
      ->condition('type', $this->id())
      ->count()
      ->execute();

    return $count;
  }

}
