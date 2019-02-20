<?php

namespace Drupal\commerce_order\Mail;

use Drupal\commerce_order\Entity\OrderInterface;

interface OrderReceiptMailInterface {

  /**
   * Sends the order receipt email.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string $to
   *   The address the email will be sent to. Must comply with RFC 2822.
   *   Defaults to the order email.
   * @param string $bcc
   *   The BCC address or addresses (separated by a comma).
   *
   * @return bool
   *   TRUE if the email was sent successfully, FALSE otherwise.
   */
  public function send(OrderInterface $order, $to = NULL, $bcc = NULL);

}
