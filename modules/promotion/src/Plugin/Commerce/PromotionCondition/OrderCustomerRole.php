<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionCondition;

use Drupal\Core\Form\FormStateInterface;
// use class to get all user roles if there is one.

/**
 * Provides an Discount for the user roles.
 *
 * @CommercePromotionCondition(
 *   id = "commerce_promotion_user_roles",
 *   label = @Translation("User Roles"),
 *   target_entity_type = "commerce_order",
 * )
 */
class OrderCustomerRole extends PromotionConditionBase {

  /**
  * {@inheritdoc}
  * - return the default value for the configuration form,
  * -- we will return the authorized user role as the default.
  */
  public function defaultConfiguration() {
     return [
       'role' => 1,
     ] + parent::defaultConfiguration();
   }

  /**
  * {@inheritdoc}
  * - return the configuration form for this condition.
  * - create a select element that gets the current system roles from the getAvailableRoles function.
  * - make sure with have a checkbox for inverse value if needed.
  */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
     $form += parent::buildConfigurationForm($form, $form_state);
     $role = $this->configuration['role'];

     // user_roles() code here
     $roles = $this->getAvailableRoles();
     $form['role'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Customer\'s Role'),
        '#default_value' => $role,
        '#options'       => $roles,
      );
      return $form;
   }

   /**
   * {@inheritdoc}
   * - get the configured selected role(s) from the configuration form.
   * - get the current order, using $this->getTargetEntity(), then
   * - check if the customer has one of the selected role(s) assigned to it, with the hasRole, pass the Role ID to this.
   * -- return true/false based on the result.
   * -- get the customer account via $order->getCustomer()
   */
   public function evaluate() {
     $role = $this->configuration['role'];
     if (empty($role)) {
       return FALSE;
     }
     // get the current order of the customer
     $order = $this->getTargetEntity();
     // get the customer account
     $customer = $order->getCustomer();
     return $customer->hasRole($role);
   }

   /**
   * {@inheritdoc}
   * - get all roles in the system, using user_roles() and build a array, with the value being the RID for the role,
   *   and the text being the human-readable label.
   * - return the array.
   */
   private function getAvailableRoles() {
     $roles = user_roles();
     $options = [];
     \Drupal::logger('commerce_promotion')->notice( '<pre>' . print_r($roles, true) . '</pre>');
     if ($roles) {
       foreach ($roles as $id => $role) {
         $options[$role->get('id')] = $role->get('label');
       }
     }
    return $options;

   }

   /**
    * {@inheritdoc}
    */
   public function summary() {
     return $this->t('Checks if a customer has the selected role.');
   }

 }
