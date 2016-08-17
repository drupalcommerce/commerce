<?php

namespace Drupal\commerce_payment\Exception;

/**
 * Thrown for declined transactions that can't be retried.
 */
class HardDeclineException extends DeclineException {}
