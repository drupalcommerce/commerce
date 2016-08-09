<?php

namespace Drupal\commerce\PluginForm;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Plugin\PluginFormInterface;

class PluginFormFactory implements PluginFormFactoryInterface {

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * Constructs a new PluginFormFactory instance.
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(ClassResolverInterface $class_resolver) {
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance(PluginWithFormsInterface $plugin, $operation) {
    $form_class = $plugin->getFormClass($operation);
    if (empty($form_class)) {
      throw new InvalidPluginDefinitionException(sprintf('The "%s" plugin did not specify a "%s" form class.', $plugin->getPluginId(), $operation));
    }
    $form_object = $this->classResolver->getInstanceFromDefinition($form_class);

    // Ensure the resulting object is a plugin form.
    if (!$form_object instanceof PluginFormInterface) {
      throw new InvalidPluginDefinitionException($plugin->getPluginId(), sprintf('The "%s" plugin did not specify a valid "%s" form class, must implement \Drupal\Core\Plugin\PluginFormInterface', $plugin->getPluginId(), $operation));
    }

    if ($form_object instanceof PluginAwareInterface) {
      $form_object->setPlugin($plugin);
    }

    return $form_object;
  }

}
