<?php

/**
 * @file
 * Default implementation of the shopping cart block template.
 *
 * Available variables:
 * - $panes: An array of checkout panes containing title and data.
 *
 * Helper variables:
 * - $form: The complete checkout review form array.
 *
 * @see template_preprocess()
 * @see template_process()
 */
?>

<div class="<?php print $classes;?>">
  <?php foreach ($panes as $pane_id => $pane): ?>
    <div class="pane <?php print $pane_id; ?>">
      <h2 class="pane-title"><?php print $pane['title']; ?></h2>
      <div class="pane-content"><?php print $pane['data']; ?></div>
    </div>
  <?php endforeach ?>
</div>
