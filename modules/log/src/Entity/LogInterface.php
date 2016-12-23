<?php

namespace Drupal\commerce_log\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

interface LogInterface extends ContentEntityInterface {

  /**
   * Gets the user ID.
   *
   * @return int|null
   *   The user ID.
   */
  public function getUserId();

  /**
   * Gets the user.
   *
   * @return \Drupal\user\UserInterface|null
   *   The user entity.
   */
  public function getUser();

  /**
   * Get the category ID.
   *
   * @return string
   *   The log category ID.
   */
  public function getCategoryId();

  /**
   * Get the category plugin.
   *
   * @return \Drupal\commerce_log\Plugin\LogCategory\LogCategoryInterface
   *   The category plugin.
   */
  public function getCategoryPlugin();

  /**
   * Get the template ID.
   *
   * @return string
   *   The template ID.
   */
  public function getTemplateId();

  /**
   * Get the template plugin.
   *
   * @return \Drupal\commerce_log\Plugin\LogTemplate\LogTemplateInterface
   *   The template plugin.
   */
  public function getTemplatePlugin();

  /**
   * Get the source entity ID.
   *
   * @return mixed
   *   The entity ID.
   */
  public function getSourceEntityId();

  /**
   * Get the source entity type.
   *
   * @return string
   *   The entity type.
   */
  public function getSourceEntityType();

  /**
   * Get the source entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The source entity.
   */
  public function getSourceEntity();

  /**
   * Get the template parameters.
   *
   * @return array
   *   The parameters.
   */
  public function getParams();

  /**
   * Set the template parameters.
   *
   * @param array $params
   *   The params.
   *
   * @return $this
   */
  public function setParams(array $params);

  /**
   * Gets the log creation timestamp.
   *
   * @return int
   *   Creation timestamp of the log.
   */
  public function getCreatedTime();

  /**
   * Sets the log creation timestamp.
   *
   * @param int $timestamp
   *   The log creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

}
