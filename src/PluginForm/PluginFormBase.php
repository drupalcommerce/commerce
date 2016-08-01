<?php

namespace Drupal\commerce\PluginForm;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides a base class for plugin forms.
 */
abstract class PluginFormBase implements PluginFormInterface, PluginAwareInterface {

  /**
   * The plugin this form is for.
   *
   * @var \Drupal\Component\Plugin\PluginInspectionInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function setPlugin(PluginInspectionInterface $plugin) {
    $this->plugin = $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Validation is optional.
  }

}
