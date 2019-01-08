<?php

namespace Drupal\commerce_checkout_test\EventSubscriber;

use Drupal\commerce_checkout\Event\CheckoutCompletionRegisterEvent;
use Drupal\commerce_checkout\Event\CheckoutEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CheckoutEvents::COMPLETION_REGISTER][] = 'onRegister';
    return $events;
  }

  /**
   * Redirects to the user edit page after account creation.
   *
   * @param \Drupal\commerce_checkout\Event\CheckoutCompletionRegisterEvent $event
   *   The event.
   */
  public function onRegister(CheckoutCompletionRegisterEvent $event) {
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $event->getAccount();
    // @see CheckoutOrderTest::testRedirectAfterRegistrationOnCheckout().
    if ($account->getAccountName() == 'bob_redirect') {
      $event->setRedirect('entity.user.edit_form', [
        'user' => $account->id(),
      ]);
    }
  }

}
