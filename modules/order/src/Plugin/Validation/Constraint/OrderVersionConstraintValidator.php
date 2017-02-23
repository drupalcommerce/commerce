<?php

namespace Drupal\commerce_order\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the OrderVersion constraint.
 */
class OrderVersionConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (isset($entity)) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $entity */
      if (!$entity->isNew()) {
        /** @var \Drupal\commerce_order\Entity\OrderInterface $saved_entity */
        $saved_entity = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId())->loadUnchanged($entity->id());
        // A change to the order version must add a violation.
        if ($saved_entity && $saved_entity->getVersion() > $entity->getVersion()) {
          $this->context->addViolation($constraint->message);
        }
      }
    }
  }

}
