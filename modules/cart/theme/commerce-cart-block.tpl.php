<?php

/**
 * @file
 * Default implementation of the shopping cart block template.
 *
 * Available variables:
 * - $contents_view: A rendered View containing the contents of the cart.
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
