<?php

namespace Drupal\commerce;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Form\FormStateInterface;

trait AjaxFormTrait {

  /**
   * Ajax handler for refreshing an entire form.
   *
   * All status messages need to be displayed when refreshing the form.
   * In large forms, it is a best practise to output these messages close
   * to the triggering element. For example, when ajax is triggered at
   * checkout, the messages should be shown above the relevant checkout pane.
   * When ['#ajax']['element'] is specified, the messages will be shown above
   * it. Otherwise, the status messages will be shown above the whole form.
   * Example:
   * <code>
   * $inline_form['apply']['#ajax']['element'] = $inline_form['#parents'];
   * </code>
   *
   * Note that both the form and the element need to have an #id specified,
   * as a workaround to core bug #2897377. This was already done for
   * checkout forms, checkout panes, and inline forms.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public static function ajaxRefreshForm(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $element = NULL;
    if (!empty($triggering_element['#ajax']['element'])) {
      $element = NestedArray::getValue($form, $triggering_element['#ajax']['element']);
    }
    // Element not specified or not found. Show messages on top of the form.
    if (!$element) {
      $element = $form;
    }
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('[data-drupal-selector="' . $form['#attributes']['data-drupal-selector'] . '"]', $form));
    $response->addCommand(new PrependCommand('[data-drupal-selector="' . $element['#attributes']['data-drupal-selector'] . '"]', ['#type' => 'status_messages']));

    return $response;
  }

}
