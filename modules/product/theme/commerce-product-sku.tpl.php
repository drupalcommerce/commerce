<?php

/**
 * @file
 * Default theme implementation to present the SKU on a product page.
 *
 * Available variables:
 * - $sku: The SKU to render.
 * - $label: If present, the string to use as the SKU label.
 *
 * Helper variables:
 * - $product: The fully loaded product object the SKU represents.
 */
?>
<?php if ($sku): ?>
  <div class="commerce-product-sku">
    <?php if ($label): ?>
      <div class="commerce-product-sku-label">
        <?php print $label; ?>
      </div>
    <?php endif; ?>
    <?php print $sku; ?>
  </div>
<?php endif; ?>
