<?php
// $Id$

/**
 * @file
 * Default implementation of the shopping cart block template.
 *
 * Available variables:
 * - $contents_view: A rendered View containing the contents of the cart.
 * - $quantity_raw: The number of items in the cart.
 * - $quantity_label: The quantity appropriate label to use for the number of
 *   items in the shopping cart; "item" or "items" by default.
 * - $quantity: A single string containing the number and label.
 * - $total_raw: The raw numeric value of the total value of items in the cart.
 * - $total_label: A text label for the total value; "Total:" by default.
 * - $total: The currency formatted total value of items in the cart.
 * - $cart_links: A rendered links array with cart and checkout links.
 *
 * Helper variables:
 * - $order: The full order object for the shopping cart.
 *
 * @see template_preprocess()
 * @see template_process()
 */
?>
<div class="cart-contents">
  <?php print $contents_view; ?>
</div>
<div class="cart-footer">
  <div class="cart-quantity">
    <span class="cart-quantity-raw"><?php print $quantity_raw; ?></span> <span class="cart-quantity-label"><?php print $quantity_label; ?></span>
  </div>
  <div class="cart-total">
    <span class="cart-total-label"><?php print $total_label; ?></span> <span class="cart-total-raw"><?php print $total; ?></span>
  </div>
  <?php print $cart_links; ?>
</div>
