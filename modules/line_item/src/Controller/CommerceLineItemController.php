<?php

/**
 * @file
 * Contains \Drupal\commerce_line_item\Controller\CommerceLineItemController.
 */

namespace Drupal\commerce_line_item\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_line_item\CommerceLineItemTypeInterface;

/**
 * Returns responses for Commerce Line item admin routes.
 */
class CommerceLineItemController extends ControllerBase {

  /**
   * Displays add content links for available line_item types.
   *
   * Redirects to admin/commerce/config/line_items/add/{line_item-type} if only one
   * type is available.
   *
   * @return array
   *   A render array for a list of the line item types that can be added.
   */
  public function addPage() {
    $line_item_types = $this->entityManager()->getStorage('commerce_line_item_type')->loadMultiple();
    // Filter out the line item types the user doesn't have access to.
    foreach ($line_item_types as $line_item_type_id => $line_item_type) {
      if (!$this->entityManager()->getAccessControlHandler('commerce_line_item')->createAccess($line_item_type_id)) {
        unset($line_item_types[$line_item_type_id]);
      }
    }

    if (count($line_item_types) == 1) {
      $line_item_type = reset($line_item_types);
      return $this->redirect('entity.commerce_line_item.add_form', array('commerce_line_item_type' => $line_item_type->id()));
    }

    return array(
      '#theme' => 'commerce_line_item_add_list',
      '#types' => $line_item_types,
    );
  }

  /**
   * Provides the line item add form.
   *
   * @param \Drupal\commerce_line_item\CommerceLineItemTypeInterface $commerce_line_item_type
   *   The line_item type entity for the line_item.
   *
   * @return array
   *   An line_item add form.
   */
  public function add(CommerceLineItemTypeInterface $commerce_line_item_type) {
    $line_item = $this->entityManager()->getStorage('commerce_line_item')->create(array(
      'type' => $commerce_line_item_type->id(),
    ));
    $form = $this->entityFormBuilder()->getForm($line_item, 'add');

    return $form;
  }

  /**
   * The title_callback for the entity.commerce_line_item.add_form route.
   *
   * @param \Drupal\commerce_line_item\CommerceLineItemTypeInterface $commerce_line_item_type
   *   The current line item type.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(CommerceLineItemTypeInterface $commerce_line_item_type) {
    return $this->t('Create @label', array('@label' => $commerce_line_item_type->label()));
  }

}
