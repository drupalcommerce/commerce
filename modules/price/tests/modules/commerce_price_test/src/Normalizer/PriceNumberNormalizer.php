<?php

namespace Drupal\commerce_price_test\Normalizer;

use Drupal\commerce_price\Plugin\Field\FieldType\PriceItem;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\serialization\Normalizer\PrimitiveDataNormalizer;

/**
 * Normalizes price field number properties.
 *
 * MySQL will store the number with trailing zeros, causing comparison problems.
 *
 * @todo remove after https://www.drupal.org/project/drupal/issues/3163853
 */
class PriceNumberNormalizer extends PrimitiveDataNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = StringData::class;

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    $supported = parent::supportsNormalization($data, $format);
    return $supported && $data instanceof StringData && $data->getParent() instanceof PriceItem && $data->getName() === 'number';
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $data = parent::normalize($object, $format, $context);;
    return number_format($data, 2);
  }

}
