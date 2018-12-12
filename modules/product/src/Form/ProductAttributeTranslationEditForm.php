<?php

namespace Drupal\commerce_product\Form;

use Drupal\commerce\InlineFormManager;
use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\config_translation\Form\ConfigTranslationEditForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the form for editing product attribute translations.
 */
class ProductAttributeTranslationEditForm extends ConfigTranslationEditForm {

  use ProductAttributeTranslationFormTrait;

  /**
   * Constructs a new ProductAttributeTranslationEditForm object.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed configuration manager.
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $config_mapper_manager
   *   The configuration mapper manager.
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *   The configurable language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce\InlineFormManager $inline_form_manager
   *   The inline form manager.
   */
  public function __construct(TypedConfigManagerInterface $typed_config_manager, ConfigMapperManagerInterface $config_mapper_manager, ConfigurableLanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, InlineFormManager $inline_form_manager) {
    parent::__construct($typed_config_manager, $config_mapper_manager, $language_manager);

    $this->entityTypeManager = $entity_type_manager;
    $this->inlineFormManager = $inline_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.typed'),
      $container->get('plugin.manager.config_translation.mapper'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_inline_form')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RouteMatchInterface $route_match = NULL, $plugin_id = NULL, $langcode = NULL) {
    $form = parent::buildForm($form, $form_state, $route_match, $plugin_id, $langcode);
    $form = $this->buildValuesForm($form, $form_state, $this->mapper->getEntity());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

}
