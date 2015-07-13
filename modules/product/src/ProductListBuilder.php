<?php

/**
 * @file
 * Contains \Drupal\commerce_product\ProductListBuilder.
 */

namespace Drupal\commerce_product;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the list builder for products.
 */
class ProductListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\commerce_product\Entity\Product */
    $uri = $entity->urlInfo();
    $options = $uri->getOptions();
    $langcode = $entity->language()->getId();
    $options += ($langcode != LanguageInterface::LANGCODE_NOT_SPECIFIED && isset($languages[$langcode]) ? ['language' => $languages[$langcode]] : []);
    $uri->setOptions($options);
    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
    ] + $uri->toRenderArray();

    return $row + parent::buildRow($entity);
  }

}
