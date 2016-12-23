<?php

namespace Drupal\commerce_log\Plugin\LogTemplate;

interface LogTemplateInterface {

  /**
   * Gets the log template ID.
   *
   * @return string
   *   The log template ID.
   */
  public function getId();

  /**
   * Gets the translated label.
   *
   * @return string
   *   The translated label.
   */
  public function getLabel();

  /**
   * Gets the log template category.
   *
   * @return string
   *   The log template category.
   */
  public function getCategory();

  /**
   * Gets the template.
   *
   * @return string
   *   The template
   */
  public function getTemplate();

}
