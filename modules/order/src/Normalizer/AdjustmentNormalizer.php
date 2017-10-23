<?php

namespace Drupal\commerce_order\Normalizer;

use Drupal\commerce_order\Adjustment;
use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Custom normalizer for 'any' typed data with Adjustment stored.
 */
class AdjustmentNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\TypedData\Plugin\DataType\Any';

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    /* @var \Drupal\Core\TypedData\Plugin\DataType\Any $data */
    if (parent::supportsNormalization($data, $format)) {
      $value = $data->getValue();
      if ($value instanceof Adjustment) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $attributes = [];
    /** @var \Drupal\commerce_order\Adjustment $adjustment */
    $adjustment = $object->getValue();
    $attributes['type'] = $adjustment->getType();
    $attributes['label'] = $adjustment->getLabel();
    $attributes['amount'] = $adjustment->getAmount()->toArray();
    $attributes['percentage'] = $adjustment->getPercentage();
    $attributes['source_id'] = $adjustment->getSourceId();
    $attributes['included'] = $adjustment->isIncluded();
    $attributes['locked'] = $adjustment->isLocked();
    return $attributes;
  }

}
