<?php

/**
 * @file
 * Provide line item id argument handler.
 */

/**
 * Argument handler to accept a line item id.
 */
class commerce_line_item_handler_argument_line_item_line_item_id extends views_handler_argument_numeric {
  /**
   * Override the behavior of title(). Get the title of the node.
   */
  function title_query() {
    $titles = array();

    $result = db_query("SELECT li.line_item_label FROM {commerce_line_item} li WHERE li.line_item_id IN (:line_item_ids)", array(':line_item_ids' => $this->value));
    foreach ($result as $line_item) {
      $titles[] = check_plain($line_item->line_item_label);
    }
    return $titles;
  }
}
