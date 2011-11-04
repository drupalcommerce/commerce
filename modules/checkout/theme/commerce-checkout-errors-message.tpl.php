<?php

/**
 * @file
 * Default implementation of the checkout errors messages template.
 *
 * Available variables:
 * - $label: the hidden heading to use in the error message div; defaults to
 *   'Errors on form.'
 * - $message: the actual message to use to indicate there are errors on the
 *   page; defaults to 'There are errors on the page. Please correct them and
 *   resubmit the form.'
 *
 * @see template_preprocess()
 * @see template_process()
 */
?>
<div class="messages error">
  <h2 class="element-invisible"><?php print $label; ?></h2>
  <?php print $message; ?>
</div>
