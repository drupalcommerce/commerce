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
	  unset($form['restrict_roles']);
	  unset($form['roles']);
  }

  function validate_argument($argument) {
  	global $user;
    $type = $this->options['type'];
    // is_numeric() can return false positives, so we ensure it's an integer.
    // However, is_integer() will always fail, since $argument is a string.
    if (is_numeric($argument) && $argument == (int)$argument) {
      if ($type == 'uid' || $type == 'either') {
        if ($argument == $GLOBALS['user']->uid) {
          // If you assign an object to a variable in PHP, the variable
          // automatically acts as a reference, not a copy, so we use
          // clone to ensure that we don't actually mess with the
          // real global $user object.
          $account = clone $GLOBALS['user'];
        }
        $where = 'uid = :argument';
      }
    }
    else {
      if ($type == 'name' || $type == 'either') {
        if ($argument == $GLOBALS['user']->name) {
          $account = clone $GLOBALS['user'];
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

    // validate for the current user
    if ($user->uid != $account->uid) {
	    // only people with the 'access administration page' get access to other people's orders
      return user_access('access administration pages'));
    }

    $this->argument->argument = $account->uid;
    $this->argument->validated_title = check_plain($account->name);
    return TRUE;
  }
}

