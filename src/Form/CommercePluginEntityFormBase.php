<?php

namespace Drupal\commerce\Form;

use Drupal\Core\Entity\EntityForm;

/**
 * @deprecated in Commerce 2.2. Set #disabled on the ID element directly.
 */
abstract class CommercePluginEntityFormBase extends EntityForm {

  /**
   * Protects the plugin's ID property's form element against changes.
   *
   * This method is assumed to be called on a completely built entity form,
   * including a form element for the plugin config entity's ID property.
   *
   * @param array $form
   *   The completely built plugin entity form array.
   *
   * @return array
   *   The updated plugin entity form array.
   */
  protected function protectPluginIdElement(array $form) {
    $entity = $this->getEntity();
    $id_key = $entity->getEntityType()->getKey('id');
    assert(isset($form[$id_key]));
    $element = &$form[$id_key];

    // Make sure the element is not accidentally re-enabled if it has already
    // been disabled.
    if (empty($element['#disabled'])) {
      $element['#disabled'] = !$entity->isNew();
    }
    return $form;
  }

}
