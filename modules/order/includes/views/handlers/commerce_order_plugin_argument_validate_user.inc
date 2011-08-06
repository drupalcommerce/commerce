<?php

/**
 * Validate whether an argument is a valid user.
 *
 * This supports either numeric arguments (UID) or strings (username) and
 * converts either one into the user's UID.  This validator also sets the
 * argument's title to the username.
 */
class commerce_order_plugin_argument_validate_user extends views_plugin_argument_validate_user {
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['restrict_roles']['#title'] = t('If the current user does not match the argument, validate the argument for users with specific roles.');

    $form['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Grant access for the selected roles'),
      '#description' => t('If no roles are selected, additional access will not be granted.'),
      '#options' => user_roles(TRUE),
      '#default_value' => $this->options['roles'],
      '#dependency' => array(
        'edit-options-validate-options-current-user-or-role-restrict-roles' => array(1),
      ),
      '#prefix' => '<div id="edit-options-validate-options-current-user-or-role-roles-wrapper">',
      '#suffix' => '</div>',
    );
  }

  function validate_argument($argument) {
    global $user;

    $type = $this->options['type'];

    // If the argument is an integer and we're accepting the argument as a uid...
    if (is_numeric($argument) && $argument == (int) $argument && $argument > 0
      && ($type == 'uid' || $type == 'either')) {
      // Build the where clause for the argument.
      $where = 'uid = :argument';

      // If the argument represents the current user...
      if ($argument == $user->uid) {
        // Set the account variable to the current user.
        $account = clone $user;
      }
    }
    // Otherwise accept the argument as a user name if specified.
    elseif ($type == 'name' || $type == 'either') {
      // Build the where clause for the argument.
      $where = "name = :argument";

      // If the argument represents the current user...
      if ($argument == $user->name) {
        // Set the account variable to the current user.
        $account = clone $user;
      }
    }

    // If we don't have a where clause, the argument is invalid.
    if (empty($where)) {
      return FALSE;
    }

    // If the argument doesn't represent the current user account...
    if (empty($account)) {
      // Load a pseudo-account object based on the validator's where clause.
      $account = db_query("SELECT uid, name FROM {users} WHERE " . $where, array(':argument' => $argument))->fetchObject();

      // If the account wasn't found, the argument is invalid.
      if (empty($account)) {
        return FALSE;
      }
    }

    // If the current user is not the account specified by the argument...
    if ($user->uid != $account->uid) {
      // And if we're granting access for non-matching users of specific roles...
      if (!empty($this->options['restrict_roles']) && !empty($this->options['roles'])) {
        // Build an array of role names based on the selected options.
        $roles = array();

        foreach ($this->options['roles'] as $rid) {
          if ($role = user_role_load($rid)) {
            $roles[$rid] = $role->name;
          }
        }

        // Look for matching roles on the current user.
        $matching_roles = array_intersect($user->roles, $roles);

        // Invalidate the argument if the user does not match any of the roles.
        if (empty($matching_roles)) {
          return FALSE;
        }
      }
      else {
        // Otherwise return FALSE if the role based fallback isn't enabled or no
        // roles are selected.
        return FALSE;
      }
    }

    $this->argument->argument = $account->uid;
    $this->argument->validated_title = check_plain($account->name);

    return TRUE;
  }
}
