<?php

namespace Drupal\commerce_product\Plugin\Field\FieldWidget;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Event\ProductVariationAjaxChangeEvent;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base structure for product variation widgets.
 *
 * Product variation widget forms depends on the 'product' being present in
 * $form_state.
 *
 * @see \Drupal\commerce_product\Plugin\Field\FieldFormatter\AddToCartFormatter::viewElements().
 */
abstract class ProductVariationWidgetBase extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The product variation storage.
   *
   * @var \Drupal\commerce_product\ProductVariationStorageInterface
   */
  protected $variationStorage;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new ProductVariationWidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityRepository = $entity_repository;
    $this->variationStorage = $entity_type_manager->getStorage('commerce_product_variation');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_order_item' && $field_name == 'purchased_entity';
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Assumes that the variation ID comes from an $element['variation'] built
    // in formElement().
    foreach ($values as $key => $value) {
      $values[$key] = [
        'target_id' => $value['variation'],
      ];
    }

    return $values;
  }

  /**
   * #ajax callback: Replaces the rendered fields on variation change.
   *
   * Assumes the existence of a 'selected_variation' in $form_state.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Render\MainContent\MainContentRendererInterface $ajax_renderer */
    $ajax_renderer = \Drupal::service('main_content_renderer.ajax');
    $request = \Drupal::request();
    $route_match = \Drupal::service('current_route_match');
    /** @var \Drupal\Core\Ajax\AjaxResponse $response */
    $response = $ajax_renderer->renderResponse($form, $request, $route_match);

    $variation = ProductVariation::load($form_state->get('selected_variation'));
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $form_state->get('product');
    if ($variation->hasTranslation($product->language()->getId())) {
      $variation = $variation->getTranslation($product->language()->getId());
    }
    /** @var \Drupal\commerce_product\ProductVariationFieldRendererInterface $variation_field_renderer */
    $variation_field_renderer = \Drupal::service('commerce_product.variation_field_renderer');
    $view_mode = $form_state->get('view_mode');
    $variation_field_renderer->replaceRenderedFields($response, $variation, $view_mode);
    // Allow modules to add arbitrary ajax commands to the response.
    $event = new ProductVariationAjaxChangeEvent($variation, $response, $view_mode);
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch(ProductEvents::PRODUCT_VARIATION_AJAX_CHANGE, $event);

    return $response;
  }

  /**
   * Gets the default variation for the widget.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   * @param array $variations
   *   An array of available variations.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface
   *   The default variation.
   */
  protected function getDefaultVariation(ProductInterface $product, array $variations) {
    $langcode = $product->language()->getId();
    $selected_variation = $this->variationStorage->loadFromContext($product);
    $selected_variation = $this->entityRepository->getTranslationFromContext($selected_variation, $langcode);
    // The returned variation must also be enabled.
    if (!in_array($selected_variation, $variations)) {
      $selected_variation = reset($variations);
    }
    return $selected_variation;
  }

  /**
   * Gets the enabled variations for the product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface[]
   *   An array of variations.
   */
  protected function loadEnabledVariations(ProductInterface $product) {
    $langcode = $product->language()->getId();
    $variations = $this->variationStorage->loadEnabled($product);
    foreach ($variations as $key => $variation) {
      $variations[$key] = $this->entityRepository->getTranslationFromContext($variation, $langcode);
    }
    return $variations;
  }

}
