<?php

namespace Drupal\commerce_tax\Plugin\Validation\Constraint;

use Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\SupportsVerificationInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the TaxNumber constraint.
 */
class TaxNumberConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($item, Constraint $constraint) {
    /** @var \Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface $item */
    if ($item->isEmpty() || strlen($item->value) > 64) {
      // The number is empty or not well formed.
      // The Length constraint is repeated here because Symfony Validator
      // doesn't stop when the first constraint validation fails.
      return;
    }
    $tax_number_type = $item->getTypePlugin();
    if (!$tax_number_type) {
      // Type not yet known.
      return;
    }

    if (!$tax_number_type->validate($item->value)) {
      // Show examples, if available, to demonstrate the right format.
      $examples = $tax_number_type->getFormattedExamples();
      $message_name = $examples ? 'invalidMessageWithExamples' : 'invalidMessage';

      $this->context->buildViolation($constraint->{$message_name})
        ->atPath('value')
        ->setParameter('%name', $item->getFieldDefinition()->getLabel())
        ->setParameter('@examples', $examples)
        ->setInvalidValue($item->value)
        ->addViolation();
      return;
    }

    // Perform verification, but only if it hasn't been done already.
    $verify = $constraint->verify && empty($item->verification_state);
    $allow_unverified = $constraint->allowUnverified;
    if ($verify && $tax_number_type instanceof SupportsVerificationInterface) {
      $result = $tax_number_type->verify($item->value);

      if ($result->isFailure() || ($result->isUnknown() && !$allow_unverified)) {
        $this->context->buildViolation($constraint->verificationFailedMessage)
          ->atPath('value')
          ->setParameter('%name', $item->getFieldDefinition()->getLabel())
          ->setInvalidValue($item->value)
          ->addViolation();
        return;
      }
    }
  }

}
