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
   * Gets the category ID.
   *
   * @return string
   *   The log category ID.
   */
  public function getCategoryId();

  /**
   * Gets the category.
   *
   * @return \Drupal\commerce_log\Plugin\LogCategory\LogCategoryInterface
   *   The category.
   */
  public function getCategory();

  /**
   * Gets the template ID.
   *
   * @return string
   *   The template ID.
   */
  public function getTemplateId();

  /**
   * Gets the template.
   *
   * @return \Drupal\commerce_log\Plugin\LogTemplate\LogTemplateInterface
   *   The template.
   */
  public function getTemplate();

  /**
   * Gets the source entity ID.
   *
   * @return mixed
   *   The entity ID.
   */
  public function getSourceEntityId();

  /**
   * Gets the source entity type ID.
   *
   * @return string
   *   The entity type ID.
   */
  public function getSourceEntityTypeId();

  /**
   * Gets the source entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The source entity.
   */
  public function getSourceEntity();

  /**
   * Gets the template parameters.
   *
   * @return array
   *   The parameters.
   */
  public function getParams();

  /**
   * Sets the template parameters.
   *
   * @param array $params
   *   The parameters.
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
