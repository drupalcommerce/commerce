<?php

namespace Drupal\commerce_payment\Exception;

/**
 * Thrown for declined transactions that can be retried.
 */
class SoftDeclineException extends DeclineException {}
