<?php

namespace Drupal\commerce\PluginForm;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for plugin forms which operate on entities.
 */
interface PluginEntityFormInterface extends PluginFormInterface {

  /**
   * Gets the form entity.
   *
   * Allows the parent form to get the updated form entity after submitForm() 
   * performs the final changes.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The form entity.
   */
  public function getEntity();

  /**
   * Sets the form entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The form entity.
   *
   * @return $this
   */
  public function setEntity(EntityInterface $entity);

}
