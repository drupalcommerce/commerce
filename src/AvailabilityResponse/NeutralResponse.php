<?php

namespace Drupal\commerce\AvailabilityResponse;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;

class NeutralResponse extends AvailabilityResponseBase {

  public function __construct(PurchasableEntityInterface $entity, Context $context) {
    $this->entity = $entity;
    $this->context = $context;
    $this->minimum = NULL;
    $this->maximum = NULL;
  }

}