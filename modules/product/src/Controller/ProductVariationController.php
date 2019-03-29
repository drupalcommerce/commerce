<?php

namespace Drupal\commerce_product\Controller;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides title callbacks for product variation routes.
 */
class ProductVariationController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new ProductVariationController.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, TranslationInterface $string_translation) {
    $this->entityRepository = $entity_repository;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('string_translation')
    );
  }

  /**
   * Provides the add title callback for product variations.
   *
   * @return string
   *   The title for the product variation add page.
   */
  public function addTitle() {
    return $this->t('Add variation');
  }

  /**
   * Provides the edit title callback for product variations.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return string
   *   The title for the product variation edit page.
   */
  public function editTitle(RouteMatchInterface $route_match) {
    $product_variation = $route_match->getParameter('commerce_product_variation');
    $product_variation = $this->entityRepository->getTranslationFromContext($product_variation);

    return $this->t('Edit %label', ['%label' => $product_variation->label()]);
  }

  /**
   * Provides the delete title callback for product variations.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return string
   *   The title for the product variation delete page.
   */
  public function deleteTitle(RouteMatchInterface $route_match) {
    $product_variation = $route_match->getParameter('commerce_product_variation');
    $product_variation = $this->entityRepository->getTranslationFromContext($product_variation);

    return $this->t('Delete %label', ['%label' => $product_variation->label()]);
  }

  /**
   * Provides the collection title callback for product variations.
   *
   * @return string
   *   The title for the product variation collection.
   */
  public function collectionTitle() {
    // Note that ProductVariationListBuilder::getForm() overrides the page
    // title. The title defined here is used only for the breadcrumb.
    return $this->t('Variations');
  }

}
