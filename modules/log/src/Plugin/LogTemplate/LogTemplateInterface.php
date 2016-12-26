<?php

namespace Drupal\commerce_log\Plugin\LogTemplate;

interface LogTemplateInterface {

  /**
   * Gets the template ID.
   *
   * @return string
   *   The template ID.
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
   * Gets the template category.
   *
   * @return string
   *   The template category.
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
