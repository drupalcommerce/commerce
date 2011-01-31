<?php
// $Id$

/**
 * Validate whether an argument is a valid user.
 *
 * This supports either numeric arguments (UID) or strings (username) and
 * converts either one into the user's UID.  This validator also sets the
 * argument's title to the username.
 */
class commerce_order_plugin_argument_validate_user extends views_plugin_argument_validate_user {
  function option_definition() {
    $options = parent::option_definition();
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    // unsetting these two form elements. We don't need them here
    $form['restrict_roles'] = array(
      '#type' => 'checkbox',
      '#title' => t("Restrict access based on the current user's roles"),
      '#description' => t("If the argument does not match the current logged in user, fall back to matching roles. This is functionally equivalent to the validator checking for 'current user or has a particular role.'"),
      '#default_value' => $this->options['restrict_roles'],
    );

    $form['roles'] = array(
      '#type' => 'select',
      '#title' => t('Users have these allowed roles'),
      '#description' => t('Select one or more roles which the current users should have.'),
      '#prefix' => '<div id="edit-options-argument-validate-commerce-user-roles-wrapper">',
      '#suffix' => '</div>',
      '#multiple' => TRUE,
      '#options' => user_roles(TRUE),
      '#default_value' => $this->options['roles'],
      '#process' => array('form_process_select', 'ctools_dependent_process'),
      '#dependency' => array(
        'edit-options-argument-validate-commerce-user-restrict-roles' => array(1),
      ),
    );
  }

  function validate_argument($argument) {
    global $user;

    $type = $this->options['type'];

    // is_numeric() can return false positives, so we ensure it's an integer.
    // However, is_integer() will always fail, since $argument is a string.
    if (is_numeric($argument) && $argument == (int) $argument) {
      if ($type == 'uid' || $type == 'either') {
        if ($argument == $user->uid) {
          // If you assign an object to a variable in PHP, the variable
          // automatically acts as a reference, not a copy, so we use
          // clone to ensure that we don't actually mess with the
          // real global $user object.
          $account = clone $user;
        }
        $where = 'uid = :argument';
      }
    }
    else {
      if ($type == 'name' || $type == 'either') {
        if ($argument == $user->name) {
          $account = clone $user;
        }
        $where = "name = :argument";
      }
    }

    // If we don't have a WHERE clause, the argument is invalid.
    if (empty($where)) {
      return FALSE;
    }

    if (!isset($account)) {
      $query = "SELECT uid, name FROM {users} WHERE $where";
      $account = db_query($query, array(':argument' => $argument))->fetchObject();
    }
    if (empty($account)) {
      // User not found.
      return FALSE;
    }

    // validation of the current user
    if ($user->uid != $account->uid) {
      if (!empty($this->options['restrict_roles']) && !empty($this->options['roles'])) {
        $roles = array();
        foreach ($this->options['roles'] as $rid) {
          $role = user_role_load($rid);
          $roles[$rid] = $role->name;
        }
        if (!(bool) array_intersect($user->roles, $roles)) {
          // no matching roles for the current user
          return FALSE;
        }
      }
    }

    $this->argument->argument = $account->uid;
    $this->argument->validated_title = check_plain($account->name);
    return TRUE;
  }
}
