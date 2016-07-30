<?php

namespace Drupal\commerce\PluginForm;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a base class for plugin forms which operate on entities.
 * 
 * @see \Drupal\commerce\PluginForm\PluginFormBase
 */
abstract class PluginEntityFormBase extends PluginFormBase {

  /**
   * The form entity.
   *
   * @var \Drupal\commerce\PluginForm\PluginWithFormsInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
    return $this;
  }    

}
