<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Validation\Constraint\ProductSkuConstraintValidator.
 */

namespace Drupal\commerce_product\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ProductSku constraint.
 */
class ProductSkuConstraintValidator extends ConstraintValidator {

    /**
     * {@inheritdoc}
     */
    public function validate($field_item, Constraint $constraint) {
        $sku= $field_item->value;
        if (isset($sku) && $sku !== '') {
            $commerce_products = \Drupal::entityManager()->getStorage('commerce_product')->loadByProperties(array('sku' => $sku));
            if (!empty($commerce_products)) {
                $this->context->addViolation($constraint->message, array('%sku' => $sku));
            }
        }
    }

}
