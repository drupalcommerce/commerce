<?php

namespace Drupal\commerce_product;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\Markup;

/**
 * Defines the list builder for product attributes.
 */
class ProductAttributeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label_id'] = $this->t('Attribute name => ID');
    $header['values'] = $this->t('Values');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $values = [];
    $i = 0;
    foreach ($entity->getValues() as $attribute_value) {
      if ($i < 100) {
        $value = $attribute_value->getName();
        $values[] = strlen($value) > 10 ? substr_replace($value, '...', 7) : $value;
      }
      $i++;
    }
    if (($count = count($values)) && $i > $count) {
      $more = Markup::create('<strong> (' . ($i - $count) . ' ' . $this->t('more values') . ' ...)</strong>');
      $values = $this->t('@values @more', ['@values' => implode(', ', $values), '@more' => $more]);
    }
    else {
      $values = implode(', ', $values);
    }
    $element_label = $entity->getElementLabel();
    $label = empty($element_label) ? '' : "({$element_label})<br />";
    $row['label_id'] = Markup::create('<em>' . $entity->label() . '</em><br />' . $label . '=> ' . $entity->id());
    $row['values'] = $values;

    return $row + parent::buildRow($entity);
  }

}
